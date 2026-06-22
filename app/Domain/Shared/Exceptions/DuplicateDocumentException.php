<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class DuplicateDocumentException extends DomainException
{
    public function errorCode(): string
    {
        return 'duplicate_document';
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
