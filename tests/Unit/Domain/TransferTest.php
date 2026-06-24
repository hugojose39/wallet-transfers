<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain;

use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Enums\TransferStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransferTest extends TestCase
{
    public function testCannotCreateTransferWithZeroAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Transfer(null, 1, 2, 0);
    }

    public function testCannotCreateTransferWithSamePayerAndPayee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Transfer(null, 1, 1, 10000);
    }

    public function testTransferStartsAsPending(): void
    {
        $transfer = new Transfer(null, 1, 2, 10000);

        $this->assertSame(TransferStatus::PENDING, $transfer->getStatus());
    }

    public function testMarkAuthorized(): void
    {
        $transfer = new Transfer(null, 1, 2, 10000);
        $transfer->markAuthorized();

        $this->assertSame(TransferStatus::AUTHORIZED, $transfer->getStatus());
    }

    public function testMarkCompleted(): void
    {
        $transfer = new Transfer(null, 1, 2, 10000);
        $transfer->markCompleted();

        $this->assertSame(TransferStatus::COMPLETED, $transfer->getStatus());
    }

    public function testMarkFailed(): void
    {
        $transfer = new Transfer(null, 1, 2, 10000);
        $transfer->markFailed();

        $this->assertSame(TransferStatus::FAILED, $transfer->getStatus());
    }

    public function testTransferGetters(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $transfer = new Transfer(42, 3, 7, 15000, TransferStatus::PENDING, $createdAt);

        $this->assertSame(42, $transfer->getId());
        $this->assertSame(3, $transfer->getPayerId());
        $this->assertSame(7, $transfer->getPayeeId());
        $this->assertSame(15000, $transfer->getAmount());
        $this->assertSame($createdAt, $transfer->getCreatedAt());
    }
}
