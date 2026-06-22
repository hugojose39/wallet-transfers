<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Testing\Client;
use PHPUnit\Framework\TestCase;

abstract class HttpTestCase extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        $this->client = make(Client::class);
    }
}
