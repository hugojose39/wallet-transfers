<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain;

use App\Domain\Shared\Exceptions\InsufficientBalanceException;
use App\Domain\User\Entities\Wallet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function testCannotCreateWalletWithNegativeBalance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Wallet(1, 1, -1);
    }

    public function testDebitReducesBalance(): void
    {
        $wallet = new Wallet(1, 1, 10000);
        $wallet->debit(3000);

        $this->assertSame(7000, $wallet->getBalance());
    }

    public function testDebitThrowsWhenInsufficientBalance(): void
    {
        $wallet = new Wallet(1, 1, 5000);

        $this->expectException(InsufficientBalanceException::class);
        $wallet->debit(10000);
    }

    public function testDebitThrowsForNonPositiveAmount(): void
    {
        $wallet = new Wallet(1, 1, 10000);

        $this->expectException(InvalidArgumentException::class);
        $wallet->debit(0);
    }

    public function testCreditIncreasesBalance(): void
    {
        $wallet = new Wallet(1, 1, 10000);
        $wallet->credit(5000);

        $this->assertSame(15000, $wallet->getBalance());
    }

    public function testCreditThrowsForNonPositiveAmount(): void
    {
        $wallet = new Wallet(1, 1, 10000);

        $this->expectException(InvalidArgumentException::class);
        $wallet->credit(-500);
    }

    public function testHasEnoughBalance(): void
    {
        $wallet = new Wallet(1, 1, 10000);

        $this->assertTrue($wallet->hasEnoughBalance(10000));
        $this->assertFalse($wallet->hasEnoughBalance(10001));
    }
}
