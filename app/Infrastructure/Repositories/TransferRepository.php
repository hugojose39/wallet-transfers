<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Transfer\Contracts\TransferRepositoryInterface;
use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Enums\TransferStatus;
use App\Infrastructure\Persistence\Models\TransferModel;
use DateTimeImmutable;

final class TransferRepository implements TransferRepositoryInterface
{
    public function save(Transfer $transfer): Transfer
    {
        $model = TransferModel::create([
            'payer_id' => $transfer->getPayerId(),
            'payee_id' => $transfer->getPayeeId(),
            'amount' => $transfer->getAmount(),
            'status' => $transfer->getStatus()->value,
        ]);

        return new Transfer(
            id: $model->id,
            payerId: $model->payer_id,
            payeeId: $model->payee_id,
            amount: (int) $model->amount,
            status: TransferStatus::from($model->status),
            createdAt: new DateTimeImmutable($model->created_at),
        );
    }

    public function findById(int $id): ?Transfer
    {
        $model = TransferModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByUserId(int $userId, int $page = 1, int $perPage = 15): array
    {
        return TransferModel::where('payer_id', $userId)
            ->orWhere('payee_id', $userId)
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (TransferModel $m) => $this->toEntity($m))
            ->all();
    }

    private function toEntity(TransferModel $model): Transfer
    {
        return new Transfer(
            id: $model->id,
            payerId: $model->payer_id,
            payeeId: $model->payee_id,
            amount: (int) $model->amount,
            status: TransferStatus::from($model->status),
            createdAt: new DateTimeImmutable($model->created_at),
        );
    }
}
