<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

return [
    'default' => [
        'handler' => [
            'class' => StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/storage/logs/hyperf.log',
                'level' => Level::Debug,
            ],
        ],
        'formatter' => [
            'class' => JsonFormatter::class,
        ],
    ],
];
