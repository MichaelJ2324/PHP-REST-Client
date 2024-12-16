<?php

namespace MRussell\REST\Tests\Stubs\Client;

use GuzzleHttp\Handler\MockHandler;
use MRussell\REST\Client\AbstractClient;

class ClientOverridenConstructor extends AbstractClient
{
    public MockHandler $mockResponses;

    public function __construct()
    {
        $this->mockResponses = new MockHandler([]);
    }
}
