<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use App\Domain\Transfer\Events\TransferCreated;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

#[Producer(exchange: 'transfer', routingKey: 'transfer.notify')]
final class TransferNotificationProducer extends ProducerMessage
{
    public function __construct(TransferCreated $event)
    {
        $this->payload = [
            'transfer_id' => $event->transferId,
            'payee_id'    => $event->payeeId,
        ];
    }
}
