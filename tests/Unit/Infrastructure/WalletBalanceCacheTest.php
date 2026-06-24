<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Infrastructure\Cache\WalletBalanceCache;
use PHPUnit\Framework\TestCase;

use function Hyperf\Support\make;

final class WalletBalanceCacheTest extends TestCase
{
    private WalletBalanceCache $cache;
    private int $userId;

    protected function setUp(): void
    {
        $this->cache = make(WalletBalanceCache::class);
        $this->userId = random_int(100000, 999999);
    }

    protected function tearDown(): void
    {
        $this->cache->invalidate($this->userId);
    }

    public function testSetAndGet(): void
    {
        $this->cache->set($this->userId, 50000);

        $this->assertSame(50000, $this->cache->get($this->userId));
    }

    public function testGetReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->cache->get(PHP_INT_MAX));
    }

    public function testInvalidate(): void
    {
        $this->cache->set($this->userId, 10000);
        $this->cache->invalidate($this->userId);

        $this->assertNull($this->cache->get($this->userId));
    }

    public function testInvalidateMany(): void
    {
        $id2 = $this->userId + 1;
        $this->cache->set($this->userId, 1000);
        $this->cache->set($id2, 2000);

        $this->cache->invalidateMany($this->userId, $id2);

        $this->assertNull($this->cache->get($this->userId));
        $this->assertNull($this->cache->get($id2));
    }
}
