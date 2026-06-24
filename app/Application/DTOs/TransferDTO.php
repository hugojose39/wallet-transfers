<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class TransferDTO
{
    public function __construct(
        public readonly int $payerId,
        public readonly int $payeeId,
        public readonly int $amount,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payerId: (int) $data['payer_id'],
            payeeId: (int) $data['payee_id'],
            amount: (int) $data['amount'],
        );
    }
}
