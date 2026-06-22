<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Redis\Redis;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class HealthController
{
    public function __construct(
        private readonly Redis $redis,
    ) {}

    public function index(ResponseInterface $response): PsrResponseInterface
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return $response->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ])->withStatus($healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            Db::select('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            return $this->redis->ping() === true || $this->redis->ping() === '+PONG';
        } catch (\Throwable) {
            return false;
        }
    }
}
