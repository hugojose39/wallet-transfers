<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Enums\TransferStatus;
use App\Domain\User\Entities\User;
use App\Domain\User\Entities\Wallet;
use App\Domain\User\Enums\UserType;
use App\Interfaces\Http\Resources\TransferResource;
use App\Interfaces\Http\Resources\UserResource;
use App\Interfaces\Http\Resources\WalletResource;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    public function testUserResource(): void
    {
        $wallet = new Wallet(1, 1, 5000);
        $user = new User(1, 'Alice', '123.456.789-00', 'alice@test.com', UserType::COMMON, $wallet);

        $data = (new UserResource($user))->toArray();

        $this->assertSame(1, $data['id']);
        $this->assertSame('Alice', $data['name']);
        $this->assertSame('123.456.789-00', $data['document']);
        $this->assertSame('alice@test.com', $data['email']);
        $this->assertSame('common', $data['type']);
    }

    public function testWalletResourceWithoutCache(): void
    {
        $wallet = new Wallet(1, 5, 20000);

        $data = (new WalletResource($wallet, false))->toArray();

        $this->assertSame(5, $data['user_id']);
        $this->assertSame(200, $data['balance']);
        $this->assertFalse($data['from_cache']);
    }

    public function testWalletResourceFromCache(): void
    {
        $wallet = new Wallet(1, 5, 10000);

        $data = (new WalletResource($wallet, true))->toArray();

        $this->assertTrue($data['from_cache']);
    }

    public function testTransferResource(): void
    {
        $createdAt = new DateTimeImmutable('2024-06-01 10:00:00');
        $transfer = new Transfer(7, 1, 2, 5000, TransferStatus::COMPLETED, $createdAt);

        $data = (new TransferResource($transfer))->toArray();

        $this->assertSame(7, $data['id']);
        $this->assertSame(1, $data['payer_id']);
        $this->assertSame(2, $data['payee_id']);
        $this->assertSame(50, $data['amount']);
        $this->assertSame('completed', $data['status']);
        $this->assertSame('2024-06-01 10:00:00', $data['created_at']);
    }
}
