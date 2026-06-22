<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\TransferNotAuthorizedException;
use App\Infrastructure\Http\AuthorizerClient;
use Psr\Log\LoggerInterface;

final class AuthorizerService
{
    public function __construct(
        private readonly AuthorizerClient $client,
        private readonly LoggerInterface $logger,
    ) {}

    public function authorize(int $payerId, int $payeeId, float $amount): void
    {
        $this->logger->info('Calling authorizer service', [
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount' => $amount,
        ]);

        $authorized = $this->client->authorize();

        if (! $authorized) {
            $this->logger->warning('Transfer not authorized by external service', [
                'payer_id' => $payerId,
                'amount' => $amount,
            ]);

            throw new TransferNotAuthorizedException('Transfer was not authorized by the external service.');
        }
    }
}
