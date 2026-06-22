<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class TransferNotAuthorizedException extends DomainException
{
    public function errorCode(): string
    {
        return 'transfer_not_authorized';
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
