<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use Hyperf\Redis\Redis;

final class WalletBalanceCache
{
    private const TTL = 60;
    private const KEY_PREFIX = 'wallet:balance:';

    public function __construct(private readonly Redis $redis) {}

    public function get(int $userId): ?int
    {
        $value = $this->redis->get($this->key($userId));

        return $value !== false ? (int) $value : null;
    }

    public function set(int $userId, int $balance): void
    {
        $this->redis->setex($this->key($userId), self::TTL, (string) $balance);
    }

    public function invalidate(int $userId): void
    {
        $this->redis->del($this->key($userId));
    }

    public function invalidateMany(int ...$userIds): void
    {
        $keys = array_map(fn (int $id) => $this->key($id), $userIds);
        $this->redis->del(...$keys);
    }

    private function key(int $userId): string
    {
        return self::KEY_PREFIX . $userId;
    }
}
