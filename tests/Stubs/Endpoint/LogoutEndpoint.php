<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Endpoint;

class LogoutEndpoint extends Endpoint
{
    protected static $_ENDPOINT_URL = 'logout';

    protected static $_DEFAULT_PROPERTIES = array(
        'httpMethod' => "POST"
    );
}
