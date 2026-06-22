<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            App\Interfaces\Http\Exceptions\DomainExceptionHandler::class,
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
        ],
    ],
];
