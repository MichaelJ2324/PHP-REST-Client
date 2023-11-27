<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Endpoint;

class RefreshEndpoint extends Endpoint
{
    protected static $_ENDPOINT_URL = 'refresh';

    protected static $_DEFAULT_PROPERTIES = array(
        'httpMethod' => "POST"
    );
}
