<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use function Hyperf\Support\env;

class AuthorizerClient
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
        try {
            $response = $this->http->get('v2/authorize');
            $data = json_decode((string) $response->getBody(), true);

            return ($data['data']['authorization'] ?? false) === true;
        } catch (ClientException $e) {
            $data = json_decode((string) $e->getResponse()->getBody(), true);

            return ($data['data']['authorization'] ?? false) === true;
        } catch (ServerException | TransferException $e) {
            throw $e;
        }
    }
}
