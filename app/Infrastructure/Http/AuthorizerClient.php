<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use GuzzleHttp\Client;
use function Hyperf\Support\env;

final class AuthorizerClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => env('AUTHORIZER_BASE_URL', 'https://util.devi.tools/api/'),
            'timeout' => 3.0,
        ]);
    }

    public function authorize(): bool
    {
        $response = $this->http->get('v2/authorize');
        $data = json_decode((string) $response->getBody(), true);

        return ($data['data']['authorization'] ?? false) === true;
    }
}
