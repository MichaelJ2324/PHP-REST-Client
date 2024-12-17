<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Endpoint;

class LogoutEndpoint extends Endpoint
{
    protected static string $_ENDPOINT_URL = 'logout';

    protected static array $_DEFAULT_PROPERTIES = [self::PROPERTY_HTTP_METHOD => "POST"];
}
