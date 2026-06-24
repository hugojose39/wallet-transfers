<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use Hyperf\Amqp\Annotation\Producer as ProducerAnnotation;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Producer;

#[ProducerAnnotation(exchange: 'transfer.notifications', routingKey: 'transfer.notify')]
final class TransferNotificationMessage extends ProducerMessage
{
    public function __construct(int $transferId, int $payeeId)
    {
        $this->payload = [
            'transfer_id' => $transferId,
            'payee_id' => $payeeId,
        ];
    }
}

class TransferNotificationProducer
{
    public function __construct(private readonly Producer $producer)
    {
    }

    public function produce(int $transferId, int $payeeId): void
    {
        $this->producer->produce(new TransferNotificationMessage($transferId, $payeeId));
    }
}
