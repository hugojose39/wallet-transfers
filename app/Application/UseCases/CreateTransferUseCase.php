<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\TransferDTO;
use App\Application\DTOs\TransferResultDTO;
use App\Application\Services\AuthorizerServiceInterface;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Events\TransferCreated;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Infrastructure\Cache\WalletBalanceCache;
use App\Infrastructure\Queue\TransferNotificationProducer;
use Hyperf\Amqp\Producer as AmqpProducer;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use RuntimeException;
use function Hyperf\Coroutine\co;

final class CreateTransferUseCase
{
    private const DISTRIBUTED_LOCK_TTL = 10;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly AuthorizerServiceInterface $authorizerService,
        private readonly LoggerInterface $logger,
        private readonly Redis $redis,
        private readonly WalletBalanceCache $walletCache,
        private readonly AmqpProducer $amqpProducer,
    ) {
    }

    public function execute(TransferDTO $dto): TransferResultDTO
    {
        $lockKey = "transfer:lock:{$dto->payerId}";

        $acquired = $this->redis->set($lockKey, '1', ['NX', 'EX' => self::DISTRIBUTED_LOCK_TTL]);

        if (!$acquired) {
            throw new RuntimeException('Another transfer is already in progress for this payer. Please try again.');
        }

        try {
            return $this->processTransfer($dto);
        } finally {
            $this->redis->del($lockKey);
        }
    }

    private function processTransfer(TransferDTO $dto): TransferResultDTO
    {
        $startTime = microtime(true);

        $payer = $this->userRepository->findByIdOrFail($dto->payerId);
        $payer->assertCanTransfer();

        $this->userRepository->findByIdOrFail($dto->payeeId);

        $this->logger->info('Starting transfer', [
            'payer_id' => $dto->payerId,
            'payee_id' => $dto->payeeId,
            'amount' => $dto->amount,
        ]);

        $this->authorizerService->authorize($dto->payerId, $dto->payeeId, $dto->amount);

        $transfer = Db::transaction(function () use ($dto): Transfer {
            $lockIds = [$dto->payerId, $dto->payeeId];
            sort($lockIds);

            $firstWallet = $this->walletRepository->findByUserIdForUpdate($lockIds[0]);
            $secondWallet = $this->walletRepository->findByUserIdForUpdate($lockIds[1]);

            $payerWallet = $firstWallet->getUserId() === $dto->payerId ? $firstWallet : $secondWallet;
            $payeeWallet = $firstWallet->getUserId() === $dto->payeeId ? $firstWallet : $secondWallet;

            $payerWallet->debit($dto->amount);
            $payeeWallet->credit($dto->amount);

            $this->walletRepository->save($payerWallet);
            $this->walletRepository->save($payeeWallet);

            $transfer = new Transfer(null, $dto->payerId, $dto->payeeId, $dto->amount);
            $transfer->markAuthorized();
            $transfer->markCompleted();

            return $this->transferRepository->save($transfer);
        });

        $this->walletCache->invalidateMany($dto->payerId, $dto->payeeId);

        $this->notifyAsync($transfer, $dto);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->logger->info('Transfer completed successfully', [
            'transfer_id' => $transfer->getId(),
            'payer_id' => $dto->payerId,
            'payee_id' => $dto->payeeId,
            'amount' => $dto->amount,
            'duration_ms' => $durationMs,
        ]);

        return TransferResultDTO::fromEntity($transfer);
    }

    private function notifyAsync(Transfer $transfer, TransferDTO $dto): void
    {
        $event = new TransferCreated(
            (int) $transfer->getId(),
            $dto->payerId,
            $dto->payeeId,
            $dto->amount,
        );

        co(function () use ($event) {
            $this->amqpProducer->produce(new TransferNotificationProducer($event));
        });
    }
}
