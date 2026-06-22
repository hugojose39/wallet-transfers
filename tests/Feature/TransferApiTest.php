<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use Hyperf\Testing\Client;
use HyperfTest\HttpTestCase;

final class TransferApiTest extends HttpTestCase
{
    public function testTransferRequiresAllFields(): void
    {
        $response = $this->client->post('/transfer', []);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testTransferPayerMustDifferFromPayee(): void
    {
        $response = $this->client->post('/transfer', [
            'value' => 100.00,
            'payer' => 1,
            'payee' => 1,
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testTransferRequiresPositiveAmount(): void
    {
        $response = $this->client->post('/transfer', [
            'value' => 0,
            'payer' => 1,
            'payee' => 2,
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testHealthCheckReturnsOkShape(): void
    {
        $response = $this->client->get('/health');
        $body = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('checks', $body);
    }

    public function testIdempotencyHeaderReturnsCachedResponse(): void
    {
        // Two identical requests with the same key should return the same response
        $key = 'test-idem-' . uniqid();

        $first = $this->client->post('/transfer', [
            'value' => 100.00,
            'payer' => 1,
            'payee' => 2,
        ], ['X-Idempotency-Key' => $key]);

        $second = $this->client->post('/transfer', [
            'value' => 100.00,
            'payer' => 1,
            'payee' => 2,
        ], ['X-Idempotency-Key' => $key]);

        // Second response should be a replay
        $this->assertSame($first->getStatusCode(), $second->getStatusCode());
    }
}
