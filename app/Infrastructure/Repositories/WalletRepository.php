<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\User\Contracts\WalletRepositoryInterface;
use App\Domain\User\Entities\Wallet;
use App\Infrastructure\Persistence\Models\WalletModel;
use RuntimeException;

final class WalletRepository implements WalletRepositoryInterface
{
    public function findByUserIdForUpdate(int $userId): Wallet
    {
        $model = WalletModel::where('user_id', $userId)->lockForUpdate()->first();

        if ($model === null) {
            throw new RuntimeException("Wallet not found for user ID {$userId}.");
        }

        return new Wallet(
            id: $model->id,
            userId: $model->user_id,
            balance: (int) $model->balance,
        );
    }

    public function save(Wallet $wallet): void
    {
        WalletModel::where('id', $wallet->getId())->update([
            'balance' => $wallet->getBalance(),
        ]);
    }
}
