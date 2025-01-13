<?php

namespace MRussell\REST\Tests\Stubs\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use MRussell\REST\Client\AbstractClient;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

class Client extends AbstractClient
{
    /**
     * @var MockHandler
     */
    public $mockResponses;

    /**
     * @var array
     */
    public $container = [];

    public string $apiURL = 'http://phpunit.tests/';

    public function __construct()
    {
        $this->mockResponses = new MockHandler([]);
        parent::__construct();
    }

    public function current(EndpointInterface $endpoint = null): EndpointInterface
    {
        if ($endpoint instanceof EndpointInterface) {
            $this->setCurrentEndpoint($endpoint);
        }

        return parent::current();
    }

    protected function initHttpHandlerStack(): void
    {
        $handler = HandlerStack::create($this->mockResponses);
        $handler->push(Middleware::history($this->container), 'history');
        $this->setHandlerStack($handler);
    }

    protected function configureAuth(): void
    {
        parent::configureAuth();
        $this->getHandlerStack()->remove('history');
        $this->getHandlerStack()->after('configureAuth', Middleware::history($this->container), 'history');
    }
}
