<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Application\Services\AuthorizerService;
use App\Domain\Shared\Exceptions\TransferNotAuthorizedException;
use App\Infrastructure\Http\AuthorizerClient;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AuthorizerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAuthorizeSucceeds(): void
    {
        /** @var AuthorizerClient|MockInterface $client */
        $client = Mockery::mock(AuthorizerClient::class);
        $client->shouldReceive('authorize')->once()->andReturn(true);

        $service = new AuthorizerService($client, new NullLogger());
        $service->authorize(1, 2, 100.00);

        $this->assertTrue(true);
    }

    public function testAuthorizeFails(): void
    {
        /** @var AuthorizerClient|MockInterface $client */
        $client = Mockery::mock(AuthorizerClient::class);
        $client->shouldReceive('authorize')->once()->andReturn(false);

        $service = new AuthorizerService($client, new NullLogger());

        $this->expectException(TransferNotAuthorizedException::class);
        $service->authorize(1, 2, 100.00);
    }
}
