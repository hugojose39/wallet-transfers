<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain;

use App\Domain\Shared\Exceptions\UnauthorizedTransferException;
use App\Domain\User\Entities\User;
use App\Domain\User\Entities\Wallet;
use App\Domain\User\Enums\UserType;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeWallet(): Wallet
    {
        return new Wallet(1, 1, 500);
    }

    public function testCommonUserCanTransfer(): void
    {
        $user = new User(1, 'Alice', '123.456.789-00', 'alice@test.com', UserType::COMMON, $this->makeWallet());

        $user->assertCanTransfer(); // no exception
        $this->assertTrue(true);
    }

    public function testMerchantCannotTransfer(): void
    {
        $user = new User(1, 'Shop', '12.345.678/0001-00', 'shop@test.com', UserType::MERCHANT, $this->makeWallet());

        $this->expectException(UnauthorizedTransferException::class);
        $user->assertCanTransfer();
    }

    public function testMerchantIsMerchant(): void
    {
        $user = new User(1, 'Shop', '12.345.678/0001-00', 'shop@test.com', UserType::MERCHANT, $this->makeWallet());

        $this->assertTrue($user->isMerchant());
    }

    public function testUserGetters(): void
    {
        $wallet = $this->makeWallet();
        $user = new User(1, 'Alice', '123.456.789-00', 'alice@test.com', UserType::COMMON, $wallet);

        $this->assertSame('123.456.789-00', $user->getDocument());
        $this->assertSame('alice@test.com', $user->getEmail());
        $this->assertSame(UserType::COMMON, $user->getType());
        $this->assertSame($wallet, $user->getWallet());
    }
}
