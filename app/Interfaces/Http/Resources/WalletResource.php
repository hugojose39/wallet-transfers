<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Resources;

use App\Domain\User\Entities\Wallet;

final class WalletResource
{
    public function __construct(
        private readonly Wallet $wallet,
        private readonly bool $fromCache = false,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->wallet->getUserId(),
            'balance' => $this->wallet->getBalance() / 100,
            'from_cache' => $this->fromCache,
        ];
    }
}
