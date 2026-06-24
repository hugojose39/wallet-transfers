<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Infrastructure;

use App\Infrastructure\Http\AuthorizerClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class AuthorizerClientTest extends TestCase
{
    private function makeClient(array $responses): AuthorizerClient
    {
        $mock = new MockHandler($responses);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $client = new AuthorizerClient();
        $prop = new ReflectionProperty(AuthorizerClient::class, 'http');
        $prop->setAccessible(true);
        $prop->setValue($client, $guzzle);

        return $client;
    }

    public function testAuthorizeReturnsTrue(): void
    {
        $client = $this->makeClient([
            new Response(200, [], '{"status":"success","data":{"authorization":true}}'),
        ]);

        $this->assertTrue($client->authorize());
    }

    public function testAuthorizeReturnsFalseOn200(): void
    {
        $client = $this->makeClient([
            new Response(200, [], '{"status":"fail","data":{"authorization":false}}'),
        ]);

        $this->assertFalse($client->authorize());
    }

    public function testAuthorizeReturnsFalseOn403(): void
    {
        $client = $this->makeClient([
            new Response(403, [], '{"status":"fail","data":{"authorization":false}}'),
        ]);

        $this->assertFalse($client->authorize());
    }

    public function testAuthorizeThrowsOnServerError(): void
    {
        $this->expectException(ServerException::class);

        $client = $this->makeClient([
            new Response(500, [], '{"message":"Internal Server Error"}'),
        ]);

        $client->authorize();
    }

    public function testAuthorizeThrowsOnConnectionFailure(): void
    {
        $this->expectException(ConnectException::class);

        $client = $this->makeClient([
            new ConnectException('Connection refused', new Request('GET', 'v2/authorize')),
        ]);

        $client->authorize();
    }
}
