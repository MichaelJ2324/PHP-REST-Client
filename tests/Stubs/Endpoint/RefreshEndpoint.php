<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Endpoint;

class RefreshEndpoint extends Endpoint
{
    protected static string $_ENDPOINT_URL = 'refresh';

    protected static array $_DEFAULT_PROPERTIES = [ self::PROPERTY_HTTP_METHOD => "POST"];
}
