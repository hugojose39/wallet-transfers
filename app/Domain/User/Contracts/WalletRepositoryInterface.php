<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\User\Entities\Wallet;

interface WalletRepositoryInterface
{
    public function findByUserIdForUpdate(int $userId): Wallet;

    public function save(Wallet $wallet): void;
}
