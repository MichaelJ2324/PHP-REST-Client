<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Data\AbstractEndpointData;

class EndpointData extends AbstractEndpointData
{
    protected static $_DEFAULT_PROPERTIES = array(
        'required' => array(
            'foo' => 'string'
        ),
        'defaults' => array(
            'bar' => 'foo'
        )
    );
}
