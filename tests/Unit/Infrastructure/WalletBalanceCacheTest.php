<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Infrastructure\Cache\WalletBalanceCache;
use Hyperf\Redis\Redis;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class WalletBalanceCacheTest extends TestCase
{
    private WalletBalanceCache $cache;

    /** @var Redis&MockInterface */
    private Redis $redis;

    protected function setUp(): void
    {
        $this->redis = Mockery::mock(Redis::class);
        $this->cache = new WalletBalanceCache($this->redis);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGet(): void
    {
        $this->redis->shouldReceive('setex')
            ->with('wallet:balance:1', 60, '50000')
            ->once();
        $this->redis->shouldReceive('get')
            ->with('wallet:balance:1')
            ->andReturn('50000');

        $this->cache->set(1, 50000);

        $this->assertSame(50000, $this->cache->get(1));
    }

    public function testGetReturnsNullWhenNotFound(): void
    {
        $this->redis->shouldReceive('get')
            ->with('wallet:balance:999')
            ->andReturn(false);

        $this->assertNull($this->cache->get(999));
    }

    public function testInvalidate(): void
    {
        $this->redis->shouldReceive('del')
            ->with('wallet:balance:1')
            ->once();

        $this->cache->invalidate(1);
        $this->addToAssertionCount(1);
    }

    public function testInvalidateMany(): void
    {
        $this->redis->shouldReceive('del')
            ->with('wallet:balance:1', 'wallet:balance:2')
            ->once();

        $this->cache->invalidateMany(1, 2);
        $this->addToAssertionCount(1);
    }
}
