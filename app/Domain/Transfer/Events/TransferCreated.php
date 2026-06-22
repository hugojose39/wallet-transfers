<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Events;

final class TransferCreated
{
    public function __construct(
        public readonly int $transferId,
        public readonly int $payerId,
        public readonly int $payeeId,
        public readonly float $amount,
    ) {}
}
