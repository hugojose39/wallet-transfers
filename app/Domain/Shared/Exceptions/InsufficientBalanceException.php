<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InsufficientBalanceException extends DomainException
{
    public function errorCode(): string
    {
        return 'insufficient_balance';
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
