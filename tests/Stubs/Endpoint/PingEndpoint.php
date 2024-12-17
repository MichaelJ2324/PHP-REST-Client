<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class PingEndpoint extends SmartEndpoint
{
    protected static string $_ENDPOINT_URL = 'ping';

    protected static array $_DEFAULT_PROPERTIES = [self::PROPERTY_HTTP_METHOD => "GET"];
}
