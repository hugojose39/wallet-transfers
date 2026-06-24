<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Interfaces\Http\Controllers\HealthController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testIndexReturnsDegradedWhenRedisFails(): void
    {
        /** @var \Hyperf\Redis\Redis|MockInterface $redis */
        $redis = Mockery::mock(\Hyperf\Redis\Redis::class);
        $redis->shouldReceive('ping')->andThrow(new \Exception('Redis unavailable'));

        $psrResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $psrResponse->shouldReceive('withStatus')->with(503)->andReturnSelf();

        /** @var ResponseInterface|MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('json')->with(Mockery::on(function (array $body) {
            return $body['status'] === 'degraded' && $body['checks']['redis'] === false;
        }))->andReturn($psrResponse);

        $controller = new HealthController($redis);
        $result = $controller->index($response);

        $this->assertSame($psrResponse, $result);
    }
}
