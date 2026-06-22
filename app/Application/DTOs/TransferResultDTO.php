<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Transfer\Entities\Transfer;

final class TransferResultDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $payerId,
        public readonly int $payeeId,
        public readonly int $amount,
        public readonly string $status,
        public readonly string $createdAt,
        private readonly Transfer $entity,
    ) {}

    public static function fromEntity(Transfer $transfer): self
    {
        return new self(
            id: $transfer->getId(),
            payerId: $transfer->getPayerId(),
            payeeId: $transfer->getPayeeId(),
            amount: $transfer->getAmount(),
            status: $transfer->getStatus()->value,
            createdAt: $transfer->getCreatedAt()->format('Y-m-d H:i:s'),
            entity: $transfer,
        );
    }

    public function toEntity(): Transfer
    {
        return $this->entity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'payer_id' => $this->payerId,
            'payee_id' => $this->payeeId,
            'amount' => $this->amount / 100,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
