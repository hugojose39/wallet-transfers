<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use App\Application\Services\AuthorizerServiceInterface;
use Hyperf\Context\ApplicationContext;
use HyperfTest\HttpTestCase;
use HyperfTest\Support\DatabaseSetupTrait;

final class TransferHappyPathTest extends HttpTestCase
{
    use DatabaseSetupTrait;

    private static int $seq = 3000;

    protected function setUp(): void
    {
        parent::setUp();

        $container = ApplicationContext::getContainer();

        // Force fresh resolution so the fake authorizer is injected
        $container->unbind(\App\Application\UseCases\CreateTransferUseCase::class);
        $container->unbind(\App\Interfaces\Http\Controllers\TransferController::class);
        $container->unbind(AuthorizerServiceInterface::class);

        $container->set(
            AuthorizerServiceInterface::class,
            new class implements AuthorizerServiceInterface {
                public function authorize(int $payerId, int $payeeId, float $amount): void {}
            }
        );
    }

    private function createUser(string $type = 'common'): int
    {
        self::$seq++;
        $doc = str_pad((string) self::$seq, 11, '0', STR_PAD_LEFT);

        $response = $this->client->request('POST', '/users', [
            'form_params' => [
                'name'     => 'User ' . self::$seq,
                'document' => $doc,
                'email'    => 'u' . self::$seq . '@happy.test',
                'password' => 'password123',
                'type'     => $type,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(201, $response->getStatusCode(), 'User creation failed: ' . json_encode($body));
        return (int) $body['data']['id'];
    }

    private function deposit(int $userId, float $amount): void
    {
        $response = $this->client->request('POST', "/users/{$userId}/wallet/deposit", [
            'form_params' => ['amount' => $amount],
        ]);
        $this->assertSame(200, $response->getStatusCode(), 'Deposit failed');
    }

    private function makeTransfer(int $payerId, int $payeeId, float $value): array
    {
        $response = $this->client->request('POST', '/transfer', [
            'form_params' => ['value' => $value, 'payer' => $payerId, 'payee' => $payeeId],
        ]);
        $body = json_decode((string) $response->getBody(), true);
        return ['status' => $response->getStatusCode(), 'body' => $body];
    }

    // ---------- Happy path ----------

    public function testSuccessfulTransferReturns201(): void
    {
        $payerId = $this->createUser('common');
        $payeeId = $this->createUser('common');
        $this->deposit($payerId, 500.00);

        $result = $this->makeTransfer($payerId, $payeeId, 100.00);

        $this->assertSame(201, $result['status'], 'Transfer body: ' . json_encode($result['body']));
        $this->assertArrayHasKey('data', $result['body']);
        $this->assertSame('completed', $result['body']['data']['status']);
        $this->assertSame($payerId, $result['body']['data']['payer_id']);
        $this->assertSame($payeeId, $result['body']['data']['payee_id']);
    }

    public function testShowTransferFoundReturns200(): void
    {
        $payerId = $this->createUser('common');
        $payeeId = $this->createUser('common');
        $this->deposit($payerId, 300.00);

        $result = $this->makeTransfer($payerId, $payeeId, 50.00);
        $this->assertSame(201, $result['status'], 'Transfer must succeed first');
        $transferId = (int) $result['body']['data']['id'];
        $this->assertGreaterThan(0, $transferId);

        $response = $this->client->request('GET', "/transfer/{$transferId}");

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($transferId, $body['data']['id']);
        $this->assertSame('completed', $body['data']['status']);
    }

    // ---------- Error paths ----------

    public function testMerchantPayerReturns409(): void
    {
        $merchantId = $this->createUser('merchant');
        $payeeId    = $this->createUser('common');
        $this->deposit($merchantId, 500.00);

        $result = $this->makeTransfer($merchantId, $payeeId, 50.00);
        $this->assertSame(409, $result['status']);
    }

    public function testInsufficientBalanceReturns409(): void
    {
        $payerId = $this->createUser('common');
        $payeeId = $this->createUser('common');
        // No deposit — zero balance

        $result = $this->makeTransfer($payerId, $payeeId, 100.00);
        $this->assertSame(409, $result['status']); // InsufficientBalanceException → caught by RuntimeException handler
    }

    public function testTransferWithUnknownPayerReturns409(): void
    {
        $payeeId = $this->createUser('common');

        $result = $this->makeTransfer(999999, $payeeId, 10.00);
        $this->assertSame(409, $result['status']); // UserNotFoundException → caught by RuntimeException handler
    }

    public function testFindByUserIdForUpdateThrowsWhenWalletNotFound(): void
    {
        // Insert a user WITHOUT a wallet (bypassing repository)
        $userId = \Hyperf\DbConnection\Db::table('users')->insertGetId([
            'name'       => 'No Wallet User',
            'document'   => '66666666666',
            'email'      => 'nowallet@hp.test',
            'password'   => 'secret',
            'type'       => 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $repo = \Hyperf\Support\make(\App\Infrastructure\Repositories\WalletRepository::class);

        $this->expectException(\RuntimeException::class);
        $repo->findByUserIdForUpdate($userId);
    }

    public function testUserModelTransfersRelation(): void
    {
        $userId = $this->createUser();
        $model = \App\Infrastructure\Persistence\Models\UserModel::find($userId);

        // Call the transfers() HasMany relationship directly
        $transfers = $model->transfers;
        $this->assertNotNull($transfers);
    }

    public function testGetTransfersAfterSuccessfulTransfer(): void
    {
        $payerId = $this->createUser('common');
        $payeeId = $this->createUser('common');
        $this->deposit($payerId, 500.00);
        $this->makeTransfer($payerId, $payeeId, 100.00);

        $response = $this->client->request('GET', "/users/{$payerId}/transfers");
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($body['data']);
        $this->assertArrayHasKey('id', $body['data'][0]);
        $this->assertArrayHasKey('status', $body['data'][0]);
    }
}
