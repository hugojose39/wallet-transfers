<?php

declare(strict_types=1);

namespace HyperfTest;

use Hyperf\Testing\Client;
use PHPUnit\Framework\TestCase;

use function Hyperf\Support\make;

abstract class HttpTestCase extends TestCase
{
    protected Client $client;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = make(Client::class);
    }
}
