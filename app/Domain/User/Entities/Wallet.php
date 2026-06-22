<?php

declare(strict_types=1);

namespace App\Domain\User\Entities;

use App\Domain\Shared\Exceptions\InsufficientBalanceException;
use InvalidArgumentException;

final class Wallet
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private int $balance,
        private int $version = 0,
    ) {
        if ($balance < 0) {
            throw new InvalidArgumentException('Wallet balance cannot be negative.');
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function hasEnoughBalance(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function debit(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        if (! $this->hasEnoughBalance($amount)) {
            throw new InsufficientBalanceException(
                sprintf(
                    'Insufficient balance. Available: R$ %s, Requested: R$ %s',
                    number_format($this->balance / 100, 2),
                    number_format($amount / 100, 2)
                )
            );
        }

        $this->balance -= $amount;
    }

    public function credit(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        $this->balance += $amount;
    }
}
