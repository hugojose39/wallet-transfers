<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application;

use App\Application\DTOs\TransferResultDTO;
use App\Domain\Transfer\Entities\Transfer;
use App\Domain\Transfer\Enums\TransferStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TransferResultDTOTest extends TestCase
{
    private function makeTransfer(): Transfer
    {
        return new Transfer(
            id: 1,
            payerId: 2,
            payeeId: 3,
            amount: 10000,
            status: TransferStatus::COMPLETED,
            createdAt: new DateTimeImmutable('2024-06-01 12:00:00'),
        );
    }

    public function testFromEntity(): void
    {
        $transfer = $this->makeTransfer();
        $dto = TransferResultDTO::fromEntity($transfer);

        $this->assertSame(1, $dto->id);
        $this->assertSame(2, $dto->payerId);
        $this->assertSame(3, $dto->payeeId);
        $this->assertSame(10000, $dto->amount);
        $this->assertSame('completed', $dto->status);
        $this->assertSame('2024-06-01 12:00:00', $dto->createdAt);
    }

    public function testToEntity(): void
    {
        $transfer = $this->makeTransfer();
        $dto = TransferResultDTO::fromEntity($transfer);

        $this->assertSame($transfer, $dto->toEntity());
    }

    public function testToArray(): void
    {
        $dto = TransferResultDTO::fromEntity($this->makeTransfer());
        $array = $dto->toArray();

        $this->assertSame(1, $array['id']);
        $this->assertSame(2, $array['payer_id']);
        $this->assertSame(3, $array['payee_id']);
        $this->assertSame(100, $array['amount']);
        $this->assertSame('completed', $array['status']);
        $this->assertSame('2024-06-01 12:00:00', $array['created_at']);
    }
}
