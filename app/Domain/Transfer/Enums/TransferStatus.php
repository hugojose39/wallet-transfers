<?php

declare(strict_types=1);

namespace App\Domain\Transfer\Enums;

enum TransferStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
