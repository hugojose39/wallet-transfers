<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    abstract public function errorCode(): string;

    abstract public function httpStatusCode(): int;
}
