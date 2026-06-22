<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\TransferDTO;
use App\Application\DTOs\TransferResultDTO;
use App\Application\Services\AuthorizerService;
use App\Application\Services\NotificationService;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Events\TransferCreated;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Hyperf\Redis\Redis;
use RuntimeException;

final class CreateTransferUseCase
{
    private const DISTRIBUTED_LOCK_TTL = 10;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly AuthorizerService $authorizerService,
        private readonly NotificationService $notificationService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly Redis $redis,
    ) {}

    public function execute(TransferDTO $dto): TransferResultDTO
    {
        $lockKey = "transfer:lock:{$dto->payerId}";

        // Acquire distributed lock to prevent concurrent transfers from same payer
        $acquired = $this->redis->set($lockKey, '1', ['NX', 'EX' => self::DISTRIBUTED_LOCK_TTL]);

        if (! $acquired) {
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

        // Step 1: Validate payer exists and is not a merchant
        $payer = $this->userRepository->findByIdOrFail($dto->payerId);
        $payer->assertCanTransfer();

        // Step 2: Validate payee exists
        $payee = $this->userRepository->findByIdOrFail($dto->payeeId);

        $this->logger->info('Starting transfer', [
            'payer_id' => $dto->payerId,
            'payee_id' => $dto->payeeId,
            'amount' => $dto->amount,
        ]);

        // Step 3: Call external authorizer (with circuit breaker via client)
        $this->authorizerService->authorize($dto->payerId, $dto->payeeId, $dto->amount);

        // Step 4: Open DB transaction with pessimistic locking
        $transfer = Db::transaction(function () use ($dto): Transfer {
            // Lock wallets in consistent ID order to prevent deadlock
            $lockIds = [$dto->payerId, $dto->payeeId];
            sort($lockIds);

            $firstWallet = $this->walletRepository->findByUserIdForUpdate($lockIds[0]);
            $secondWallet = $this->walletRepository->findByUserIdForUpdate($lockIds[1]);

            $payerWallet = $firstWallet->getUserId() === $dto->payerId ? $firstWallet : $secondWallet;
            $payeeWallet = $firstWallet->getUserId() === $dto->payeeId ? $firstWallet : $secondWallet;

            // Validate sufficient balance inside the lock
            $payerWallet->debit($dto->amount);
            $payeeWallet->credit($dto->amount);

            // Persist wallet changes
            $this->walletRepository->save($payerWallet);
            $this->walletRepository->save($payeeWallet);

            // Create and persist transfer record
            $transfer = new Transfer(null, $dto->payerId, $dto->payeeId, $dto->amount);
            $transfer->markAuthorized();
            $transfer->markCompleted();

            return $this->transferRepository->save($transfer);
        });

        // Step 5: Dispatch event (invalidates cache, triggers notification)
        $this->eventDispatcher->dispatch(new TransferCreated(
            $transfer->getId(),
            $dto->payerId,
            $dto->payeeId,
            $dto->amount,
        ));

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
}
