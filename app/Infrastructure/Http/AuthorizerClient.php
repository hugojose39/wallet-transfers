<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\CircuitBreaker\Annotation\CircuitBreaker;
use Hyperf\Retry\Annotation\Retry;
use Psr\Log\LoggerInterface;

final class AuthorizerClient
{
    private Client $http;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->http = new Client([
            'base_uri' => env('AUTHORIZER_BASE_URL', 'https://util.devi.tools/api/v2'),
            'timeout' => 3.0,
        ]);
    }

    #[CircuitBreaker(
        fallback: 'authorizeFallback',
        sleepWindowInMilliseconds: 30000,
        requestVolumeThreshold: 3,
    )]
    #[Retry(maxAttempts: 3, base: 200, multiplier: 2.0)]
    public function authorize(): bool
    {
        $response = $this->http->get('/authorize');
        $data = json_decode((string) $response->getBody(), true);

        return ($data['data']['authorization'] ?? false) === true;
    }

    public function authorizeFallback(): bool
    {
        $this->logger->error('Authorizer circuit breaker open — denying transfer');

        return false;
    }
}
