<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class AuthEndpoint extends SmartEndpoint
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_HTTP_METHOD => "POST",
        self::PROPERTY_URL => 'authenticate',
    ];
}
