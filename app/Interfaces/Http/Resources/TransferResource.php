<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Resources;

use App\Domain\Transfer\Entities\Transfer;

final class TransferResource
{
    public function __construct(private readonly Transfer $transfer)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->transfer->getId(),
            'payer_id' => $this->transfer->getPayerId(),
            'payee_id' => $this->transfer->getPayeeId(),
            'amount' => $this->transfer->getAmount() / 100,
            'status' => $this->transfer->getStatus()->value,
            'created_at' => $this->transfer->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
