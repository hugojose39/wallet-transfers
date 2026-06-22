<?php

declare(strict_types=1);

return [
    'http' => [
        App\Interfaces\Http\Middleware\IdempotencyMiddleware::class,
    ],
];
