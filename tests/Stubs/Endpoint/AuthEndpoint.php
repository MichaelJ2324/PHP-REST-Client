<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class AuthEndpoint extends SmartEndpoint
{
    protected static $_ENDPOINT_URL = 'authenticate';

    protected static $_DEFAULT_PROPERTIES = array(
        'httpMethod' => "POST"
    );
}
