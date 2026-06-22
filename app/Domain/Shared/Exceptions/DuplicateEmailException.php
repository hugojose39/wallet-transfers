<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class DuplicateEmailException extends DomainException
{
    public function errorCode(): string
    {
        return 'duplicate_email';
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
