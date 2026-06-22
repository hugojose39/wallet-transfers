<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Entities;

use App\Domain\Transfer\Enums\TransferStatus;
use DateTimeImmutable;
use InvalidArgumentException;

final class Transfer
{
    private TransferStatus $status;
    private DateTimeImmutable $createdAt;

    public function __construct(
        private readonly ?int $id,
        private readonly int $payerId,
        private readonly int $payeeId,
        private readonly int $amount,
        TransferStatus $status = TransferStatus::PENDING,
        ?DateTimeImmutable $createdAt = null,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be positive.');
        }

        if ($payerId === $payeeId) {
            throw new InvalidArgumentException('Payer and payee must be different users.');
        }

        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPayerId(): int
    {
        return $this->payerId;
    }

    public function getPayeeId(): int
    {
        return $this->payeeId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getStatus(): TransferStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAuthorized(): void
    {
        $this->status = TransferStatus::AUTHORIZED;
    }

    public function markCompleted(): void
    {
        $this->status = TransferStatus::COMPLETED;
    }

    public function markFailed(): void
    {
        $this->status = TransferStatus::FAILED;
    }
}
