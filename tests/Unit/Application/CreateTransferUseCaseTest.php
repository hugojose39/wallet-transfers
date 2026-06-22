<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application;

use App\Application\DTOs\TransferDTO;
use App\Application\Services\AuthorizerService;
use App\Application\Services\NotificationService;
use App\Application\UseCases\CreateTransferUseCase;
use App\Domain\Shared\Exceptions\InsufficientBalanceException;
use App\Domain\Shared\Exceptions\TransferNotAuthorizedException;
use App\Domain\Shared\Exceptions\UnauthorizedTransferException;
use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Enums\TransferStatus;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Domain\User\Entities\User;
use App\Domain\User\Entities\Wallet;
use App\Domain\User\Enums\UserType;
use DateTimeImmutable;
use Hyperf\Redis\Redis;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

final class CreateTransferUseCaseTest extends TestCase
{
    private UserRepositoryInterface|MockInterface $userRepo;
    private WalletRepositoryInterface|MockInterface $walletRepo;
    private TransferRepositoryInterface|MockInterface $transferRepo;
    private AuthorizerService|MockInterface $authorizer;
    private NotificationService|MockInterface $notification;
    private EventDispatcherInterface|MockInterface $dispatcher;
    private Redis|MockInterface $redis;
    private CreateTransferUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepo = Mockery::mock(UserRepositoryInterface::class);
        $this->walletRepo = Mockery::mock(WalletRepositoryInterface::class);
        $this->transferRepo = Mockery::mock(TransferRepositoryInterface::class);
        $this->authorizer = Mockery::mock(AuthorizerService::class);
        $this->notification = Mockery::mock(NotificationService::class);
        $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->redis = Mockery::mock(Redis::class);

        $this->useCase = new CreateTransferUseCase(
            $this->userRepo,
            $this->walletRepo,
            $this->transferRepo,
            $this->authorizer,
            $this->notification,
            $this->dispatcher,
            new NullLogger(),
            $this->redis,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testMerchantPayerIsRejected(): void
    {
        $merchant = $this->makeUser(1, UserType::MERCHANT, 100000);

        $this->redis->shouldReceive('set')->andReturn(true);
        $this->redis->shouldReceive('del');
        $this->userRepo->shouldReceive('findByIdOrFail')->with(1)->andReturn($merchant);

        $this->expectException(UnauthorizedTransferException::class);
        $this->useCase->execute(new TransferDTO(1, 2, 10000));
    }

    public function testTransferDeniedByAuthorizer(): void
    {
        $payer = $this->makeUser(1, UserType::COMMON, 50000);
        $payee = $this->makeUser(2, UserType::COMMON, 10000);

        $this->redis->shouldReceive('set')->andReturn(true);
        $this->redis->shouldReceive('del');
        $this->userRepo->shouldReceive('findByIdOrFail')->with(1)->andReturn($payer);
        $this->userRepo->shouldReceive('findByIdOrFail')->with(2)->andReturn($payee);
        $this->authorizer->shouldReceive('authorize')->andThrow(new TransferNotAuthorizedException());

        $this->expectException(TransferNotAuthorizedException::class);
        $this->useCase->execute(new TransferDTO(1, 2, 10000));
    }

    public function testConcurrentLockPreventsDoubleTransfer(): void
    {
        $this->redis->shouldReceive('set')->andReturn(false); // lock already held

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/in progress/');
        $this->useCase->execute(new TransferDTO(1, 2, 5000));
    }

    private function makeUser(int $id, UserType $type, int $balance): User
    {
        $wallet = new Wallet($id, $id, $balance);
        return new User($id, "User {$id}", "doc{$id}", "u{$id}@test.com", $type, $wallet);
    }
}
