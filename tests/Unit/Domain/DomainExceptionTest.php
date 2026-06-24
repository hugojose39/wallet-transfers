<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain;

use App\Domain\Shared\Exceptions\DuplicateDocumentException;
use App\Domain\Shared\Exceptions\DuplicateEmailException;
use App\Domain\Shared\Exceptions\InsufficientBalanceException;
use App\Domain\Shared\Exceptions\TransferNotAuthorizedException;
use App\Domain\Shared\Exceptions\UnauthorizedTransferException;
use App\Domain\Shared\Exceptions\UserNotFoundException;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTest extends TestCase
{
    public function testDuplicateDocumentException(): void
    {
        $e = new DuplicateDocumentException();
        $this->assertSame('duplicate_document', $e->errorCode());
        $this->assertSame(422, $e->httpStatusCode());
    }

    public function testDuplicateEmailException(): void
    {
        $e = new DuplicateEmailException();
        $this->assertSame('duplicate_email', $e->errorCode());
        $this->assertSame(422, $e->httpStatusCode());
    }

    public function testInsufficientBalanceException(): void
    {
        $e = new InsufficientBalanceException();
        $this->assertSame('insufficient_balance', $e->errorCode());
        $this->assertSame(422, $e->httpStatusCode());
    }

    public function testTransferNotAuthorizedException(): void
    {
        $e = new TransferNotAuthorizedException();
        $this->assertSame('transfer_not_authorized', $e->errorCode());
        $this->assertSame(422, $e->httpStatusCode());
    }

    public function testUnauthorizedTransferException(): void
    {
        $e = new UnauthorizedTransferException();
        $this->assertSame('unauthorized_transfer', $e->errorCode());
        $this->assertSame(403, $e->httpStatusCode());
    }

    public function testUserNotFoundException(): void
    {
        $e = new UserNotFoundException();
        $this->assertSame('user_not_found', $e->errorCode());
        $this->assertSame(404, $e->httpStatusCode());
    }
}
