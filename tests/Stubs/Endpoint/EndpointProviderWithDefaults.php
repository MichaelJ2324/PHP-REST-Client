<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Provider\DefaultEndpointProvider;

class EndpointProviderWithDefaults extends DefaultEndpointProvider
{
    protected static $_DEFAULT_ENDPOINTS = ['auth' => ['class' => \MRussell\REST\Tests\Stubs\Endpoint\AuthEndpoint::class], 'refresh' => ['class' => \MRussell\REST\Tests\Stubs\Endpoint\RefreshEndpoint::class], 'logout' => ['class' => \MRussell\REST\Tests\Stubs\Endpoint\LogoutEndpoint::class], 'ping' => ['class' => \MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint::class]];
}
