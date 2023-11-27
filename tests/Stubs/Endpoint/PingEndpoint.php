<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class PingEndpoint extends SmartEndpoint
{
    protected static $_ENDPOINT_URL = 'ping';

    protected static $_DEFAULT_PROPERTIES = array(
        'httpMethod' => "GET"
    );
}
