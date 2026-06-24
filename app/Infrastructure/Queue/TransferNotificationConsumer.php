<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

use App\Infrastructure\Http\NotifierClient;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

#[Consumer(
    exchange: 'transfer.notifications',
    routingKey: 'transfer.notify',
    queue: 'transfer.notification.queue',
    nums: 2,
)]
final class TransferNotificationConsumer extends ConsumerMessage
{
    public function __construct(
        private readonly NotifierClient $notifierClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function consumeMessage(mixed $data, AMQPMessage $message): Result
    {
        $transferId = (int) $data['transfer_id'];
        $payeeId = (int) $data['payee_id'];

        $this->logger->info('Processing notification', [
            'transfer_id' => $transferId,
            'payee_id' => $payeeId,
        ]);

        $success = $this->notifierClient->notify($transferId, $payeeId);

        if ($success) {
            $this->logger->info('Notification sent successfully', ['transfer_id' => $transferId]);
            return Result::ACK;
        }

        $this->logger->warning('Notification failed, will requeue', ['transfer_id' => $transferId]);
        return Result::REQUEUE;
    }
}
