<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

final class NotifierClient
{
    private Client $http;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->http = new Client([
            'base_uri' => env('NOTIFIER_BASE_URL', 'https://util.devi.tools/api/v1'),
            'timeout' => 5.0,
        ]);
    }

    public function notify(int $transferId, int $payeeId): bool
    {
        try {
            $response = $this->http->post('/notify', [
                'json' => [
                    'transfer_id' => $transferId,
                    'payee_id' => $payeeId,
                ],
            ]);

            return $response->getStatusCode() === 204;
        } catch (GuzzleException $e) {
            $this->logger->warning('Notification service unavailable', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
