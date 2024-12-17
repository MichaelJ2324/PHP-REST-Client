<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Data\AbstractEndpointData;

class EndpointData extends AbstractEndpointData
{
    protected static $_DEFAULT_PROPERTIES = ['required' => ['foo' => 'string'], 'defaults' => ['bar' => 'foo']];
}
