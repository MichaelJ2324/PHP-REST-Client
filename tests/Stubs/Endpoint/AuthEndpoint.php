<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class AuthEndpoint extends SmartEndpoint
{
    protected static string $_ENDPOINT_URL = 'authenticate';

    protected static array $_DEFAULT_PROPERTIES = ['httpMethod' => "POST"];
}
