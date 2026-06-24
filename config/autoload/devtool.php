<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'generator' => [
        'amqp' => [
            'consumer' => [
                'namespace' => 'App\\Infrastructure\\Amqp\\Consumer',
            ],
            'producer' => [
                'namespace' => 'App\\Infrastructure\\Amqp\\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'App\\Infrastructure\\Aspect',
        ],
        'command' => [
            'namespace' => 'App\\Infrastructure\\Command',
        ],
        'controller' => [
            'namespace' => 'App\\Infrastructure\\Controller',
        ],
        'job' => [
            'namespace' => 'App\\Infrastructure\\Job',
        ],
        'listener' => [
            'namespace' => 'App\\Infrastructure\\Listener',
        ],
        'middleware' => [
            'namespace' => 'App\\Infrastructure\\Middleware',
        ],
        'Process' => [
            'namespace' => 'App\\Infrastructure\\Processes',
        ],
    ],
];