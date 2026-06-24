<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Application;

use App\Application\DTOs\TransferDTO;
use PHPUnit\Framework\TestCase;

final class TransferDTOTest extends TestCase
{
    public function testFromArray(): void
    {
        $dto = TransferDTO::fromArray([
            'payer_id' => '5',
            'payee_id' => '8',
            'amount' => '20000',
        ]);

        $this->assertSame(5, $dto->payerId);
        $this->assertSame(8, $dto->payeeId);
        $this->assertSame(20000, $dto->amount);
    }
}
