<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Testing\Client;
use PHPUnit\Framework\TestCase;

use function Hyperf\Support\make;

abstract class HttpTestCase extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = make(Client::class);
    }
}
