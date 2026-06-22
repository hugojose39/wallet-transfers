<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Middleware;

use Hyperf\Redis\Redis;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Response;

final class IdempotencyMiddleware implements MiddlewareInterface
{
    private const KEY_PREFIX = 'idem:';
    private const TTL = 86400; // 24 hours

    public function __construct(private readonly Redis $redis) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only apply to POST requests
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        $idempotencyKey = $request->getHeaderLine('X-Idempotency-Key');

        if ($idempotencyKey === '') {
            return $handler->handle($request);
        }

        $redisKey = self::KEY_PREFIX . $idempotencyKey;
        $cached = $this->redis->get($redisKey);

        if ($cached !== false) {
            $stored = json_decode($cached, true);

            return (new Response())
                ->withStatus($stored['status'])
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Idempotent-Replayed', 'true')
                ->withBody(new SwooleStream($stored['body']));
        }

        $response = $handler->handle($request);

        // Cache only successful or client-error responses (not 5xx)
        if ($response->getStatusCode() < 500) {
            $payload = json_encode([
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ]);

            $this->redis->setex($redisKey, self::TTL, $payload);
        }

        return $response;
    }
}
