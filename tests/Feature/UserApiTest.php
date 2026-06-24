<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use HyperfTest\HttpTestCase;
use HyperfTest\Support\DatabaseSetupTrait;

final class UserApiTest extends HttpTestCase
{
    use DatabaseSetupTrait;

    private static int $seq = 0;

    private function uniqueDoc(): string
    {
        return sprintf('%011d', ++self::$seq . rand(1000, 9999));
    }

    private function createUserPayload(string $type = 'common'): array
    {
        $uid = uniqid();
        return [
            'name' => 'Test User ' . $uid,
            'document' => $this->uniqueDoc(),
            'email' => 'user_' . $uid . '@test.com',
            'password' => 'password123',
            'type' => $type,
        ];
    }

    private function createUser(array $payload = []): int
    {
        if (empty($payload)) {
            $payload = $this->createUserPayload();
        }
        $response = $this->client->request('POST', '/users', ['form_params' => $payload]);
        return $response->getStatusCode() === 201
            ? json_decode((string) $response->getBody(), true)['data']['id']
            : 0;
    }

    // ---------- POST /users ----------

    public function testCreateUserRequiresFields(): void
    {
        $response = $this->client->request('POST', '/users', ['form_params' => []]);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testCreateUserRequiresValidType(): void
    {
        $payload = $this->createUserPayload();
        $payload['type'] = 'invalid';
        $response = $this->client->request('POST', '/users', ['form_params' => $payload]);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testCreateUserReturns201(): void
    {
        $response = $this->client->request('POST', '/users', [
            'form_params' => $this->createUserPayload(),
        ]);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertSame('common', $body['data']['type']);
    }

    public function testCreateMerchantUser(): void
    {
        $response = $this->client->request('POST', '/users', [
            'form_params' => $this->createUserPayload('merchant'),
        ]);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('merchant', $body['data']['type']);
    }

    public function testCreateUserWithDuplicateDocumentFails(): void
    {
        $payload = $this->createUserPayload();
        $this->client->request('POST', '/users', ['form_params' => $payload]);

        $payload2 = $this->createUserPayload();
        $payload2['document'] = $payload['document'];
        $response = $this->client->request('POST', '/users', ['form_params' => $payload2]);

        $this->assertSame(422, $response->getStatusCode());
    }

    // ---------- GET /users/{id}/wallet ----------

    public function testGetWalletReturns200(): void
    {
        $id = $this->createUser();
        $this->assertGreaterThan(0, $id);

        $response = $this->client->request('GET', "/users/{$id}/wallet");
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('balance', $body['data']);
    }

    public function testGetWalletReturnsCachedOnSecondCall(): void
    {
        $id = $this->createUser();
        $this->client->request('GET', "/users/{$id}/wallet"); // first call sets cache

        $response = $this->client->request('GET', "/users/{$id}/wallet"); // second call hits cache
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['data']['from_cache']);
    }

    public function testGetWalletReturns404WhenUserNotFound(): void
    {
        $response = $this->client->request('GET', '/users/999999/wallet');
        $this->assertSame(404, $response->getStatusCode());
    }

    // ---------- GET /users/{id}/transfers ----------

    public function testGetTransfersReturnsEmptyList(): void
    {
        $id = $this->createUser();
        $response = $this->client->request('GET', "/users/{$id}/transfers");
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $body['data']);
    }

    public function testGetTransfersReturns404WhenUserNotFound(): void
    {
        $response = $this->client->request('GET', '/users/999999/transfers');
        $this->assertSame(404, $response->getStatusCode());
    }

    // ---------- POST /users/{id}/wallet/deposit ----------

    public function testDepositReturnsUpdatedBalance(): void
    {
        $id = $this->createUser();

        $response = $this->client->request('POST', "/users/{$id}/wallet/deposit", [
            'form_params' => ['amount' => 100.00],
        ]);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(100, $body['data']['balance']);
    }

    public function testDepositRequiresPositiveAmount(): void
    {
        $id = $this->createUser();
        $response = $this->client->request('POST', "/users/{$id}/wallet/deposit", [
            'form_params' => ['amount' => 0],
        ]);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testDepositReturns404WhenUserNotFound(): void
    {
        $response = $this->client->request('POST', '/users/999999/wallet/deposit', [
            'form_params' => ['amount' => 50.00],
        ]);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ---------- GET /transfer/{id} ----------

    public function testShowTransferReturns404WhenNotFound(): void
    {
        $response = $this->client->request('GET', '/transfer/999999');
        $this->assertSame(404, $response->getStatusCode());
    }
}
