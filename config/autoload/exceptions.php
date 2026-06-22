<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            App\Interfaces\Http\Exceptions\DomainExceptionHandler::class,
            App\Interfaces\Http\Exceptions\ValidationExceptionHandler::class,
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            App\Interfaces\Http\Exceptions\UnhandledExceptionHandler::class,
        ],
    ],
];
