<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class UnauthorizedTransferException extends DomainException
{
    public function errorCode(): string
    {
        return 'unauthorized_transfer';
    }

    public function httpStatusCode(): int
    {
        return 403;
    }
}
