<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Infrastructure\Queue\TransferNotificationProducer;
use Psr\Log\LoggerInterface;

final class NotificationService
{
    public function __construct(
        private readonly TransferNotificationProducer $producer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyAsync(int $transferId, int $payeeId): void
    {
        $this->logger->info('Dispatching notification job', [
            'transfer_id' => $transferId,
            'payee_id' => $payeeId,
        ]);

        $this->producer->produce($transferId, $payeeId);
    }
}
