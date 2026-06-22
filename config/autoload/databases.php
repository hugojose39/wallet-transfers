<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'mysql'),
        'port' => (int) env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'wallet_transfers'),
        'username' => env('DB_USERNAME', 'wallet'),
        'password' => env('DB_PASSWORD', 'secret'),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 20,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
        'cache' => [
            'handler' => Hyperf\ModelCache\Handler\RedisHandler::class,
            'cache_key' => '{mc:%s:m:%s}:%s:%s',
            'prefix' => 'mc',
            'ttl' => 3600,
            'empty_model_ttl' => 60,
            'load_script' => true,
            'use_default_value' => false,
        ],
        'commands' => [
            'gen:model' => [
                'path' => 'app/Infrastructure/Persistence/Models',
                'force_casts' => true,
                'inheritance' => 'Model',
            ],
        ],
    ],
];
