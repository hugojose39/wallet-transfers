<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\Transfer\Events\TransferCreated;
use App\Infrastructure\Cache\WalletBalanceCache;
use App\Infrastructure\Queue\TransferNotificationMessage;
use Hyperf\Amqp\Producer;
use Hyperf\Event\Annotation\Listener;
use Psr\EventDispatcher\ListenerProviderInterface;

#[Listener]
final class InvalidateWalletCacheListener implements ListenerProviderInterface
{
    public function __construct(
        private readonly WalletBalanceCache $cache,
        private readonly Producer $producer,
    ) {
    }

    public function getListenersForEvent(object $event): iterable
    {
        if ($event instanceof TransferCreated) {
            yield [$this, 'handle'];
        }
    }

    public function handle(TransferCreated $event): void
    {
        $this->cache->invalidateMany($event->payerId, $event->payeeId);

        $this->producer->produce(
            new TransferNotificationMessage($event->transferId, $event->payeeId)
        );
    }
}
