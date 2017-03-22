<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Provider\AbstractEndpointProvider;

class EndpointProvider extends AbstractEndpointProvider
{
    protected static $_DEFAULT_ENDPOINTS = array(
        'auth' => array(
            'class' => 'MRussell\\REST\\Tests\\Stubs\\Endpoint\\AuthEndpoint'
        )
    );
}