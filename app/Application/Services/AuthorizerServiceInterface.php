<?php

declare(strict_types=1);

namespace App\Application\Services;

interface AuthorizerServiceInterface
{
    public function authorize(int $payerId, int $payeeId, float $amount): void;
}
