<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;


use MRussell\Http\Request\JSON;
use MRussell\REST\Endpoint\Provider\DefaultEndpointProvider;

class EndpointProviderWithDefaults extends DefaultEndpointProvider
{
    protected static $_DEFAULT_ENDPOINTS = array(
        'auth' => array(
            'class' => 'MRussell\REST\Tests\Stubs\Endpoint\AuthEndpoint'
        ),
        'refresh' => array(
            'class' => 'MRussell\REST\Tests\Stubs\Endpoint\RefreshEndpoint'
        ),
        'logout' => array(
            'class' => 'MRussell\REST\Tests\Stubs\Endpoint\LogoutEndpoint'
        ),
        'ping' => array(
            'class' => 'MRussell\REST\Endpoint\JSON\Endpoint',
            'properties' => array(
                'url' => 'ping',
                'httpMethod' => JSON::HTTP_GET
            )
        )
    );
}