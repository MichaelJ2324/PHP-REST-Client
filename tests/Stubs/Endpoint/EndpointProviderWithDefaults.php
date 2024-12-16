<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Provider\DefaultEndpointProvider;

class EndpointProviderWithDefaults extends DefaultEndpointProvider
{
    protected static array $_DEFAULT_ENDPOINTS = [
        'auth' => ['class' => AuthEndpoint::class],
        'refresh' => ['class' => RefreshEndpoint::class],
        'logout' => ['class' => LogoutEndpoint::class],
        'ping' => ['class' => PingEndpoint::class],
    ];
}
