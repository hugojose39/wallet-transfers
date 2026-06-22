<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('AMQP_HOST', 'rabbitmq'),
        'port' => (int) env('AMQP_PORT', 5672),
        'user' => env('AMQP_USER', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest'),
        'vhost' => env('AMQP_VHOST', '/'),
        'concurrent' => [
            'limit' => 5,
        ],
        'pool' => [
            'connections' => 5,
        ],
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            'connection_timeout' => 3,
            'read_write_timeout' => 6,
            'context' => null,
            'keepalive' => false,
            'heartbeat' => 3,
            'channel_rpc_timeout' => 0,
            'close_on_destruct' => false,
        ],
    ],
];
