<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class UserNotFoundException extends DomainException
{
    public function errorCode(): string
    {
        return 'user_not_found';
    }

    public function httpStatusCode(): int
    {
        return 404;
    }
}
