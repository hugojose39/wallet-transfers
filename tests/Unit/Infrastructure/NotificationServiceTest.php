<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Application\Services\NotificationService;
use App\Infrastructure\Queue\TransferNotificationProducer;
use Hyperf\Amqp\Producer;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class NotificationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testNotifyAsync(): void
    {
        /** @var Producer|MockInterface $amqpProducer */
        $amqpProducer = Mockery::mock(Producer::class);
        $amqpProducer->shouldReceive('produce')->once()->andReturn(true);

        $producer = new TransferNotificationProducer($amqpProducer);
        $service = new NotificationService($producer, new NullLogger());

        $service->notifyAsync(10, 5);

        $this->assertTrue(true); // Mockery verifies the call expectation
    }
}
