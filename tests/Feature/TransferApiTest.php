<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use HyperfTest\HttpTestCase;

final class TransferApiTest extends HttpTestCase
{
    public function testTransferRequiresAllFields(): void
    {
        $response = $this->client->request('POST', '/transfer');

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testTransferPayerMustDifferFromPayee(): void
    {
        $response = $this->client->request('POST', '/transfer', [
            'form_params' => ['value' => 100.00, 'payer' => 1, 'payee' => 1],
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testTransferRequiresPositiveAmount(): void
    {
        $response = $this->client->request('POST', '/transfer', [
            'form_params' => ['value' => 0, 'payer' => 1, 'payee' => 2],
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testHealthCheckReturnsOkShape(): void
    {
        $body = $this->client->get('/health');

        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('checks', $body);
    }

    public function testDocsSpecReturns200(): void
    {
        $response = $this->client->request('GET', '/docs/openapi.yaml');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('openapi', (string) $response->getBody());
    }

    public function testDocsUiReturns200(): void
    {
        $response = $this->client->request('GET', '/docs');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIdempotencyHeaderReturnsCachedResponse(): void
    {
        $key = 'test-idem-' . uniqid();

        $first = $this->client->request('POST', '/transfer', [
            'form_params' => ['value' => 100.00, 'payer' => 1, 'payee' => 2],
            'headers' => ['X-Idempotency-Key' => $key],
        ]);

        $second = $this->client->request('POST', '/transfer', [
            'form_params' => ['value' => 100.00, 'payer' => 1, 'payee' => 2],
            'headers' => ['X-Idempotency-Key' => $key],
        ]);

        $this->assertSame($first->getStatusCode(), $second->getStatusCode());
    }
}
