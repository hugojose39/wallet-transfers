<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain;

use App\Domain\Transfer\Events\TransferCreated;
use PHPUnit\Framework\TestCase;

final class TransferCreatedEventTest extends TestCase
{
    public function testEventHoldsData(): void
    {
        $event = new TransferCreated(10, 1, 2, 250.00);

        $this->assertSame(10, $event->transferId);
        $this->assertSame(1, $event->payerId);
        $this->assertSame(2, $event->payeeId);
        $this->assertSame(250.00, $event->amount);
    }
}
