<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\SmartEndpoint;

class PingEndpoint extends SmartEndpoint
{
    protected static array $_DEFAULT_PROPERTIES = [self::PROPERTY_URL => "ping"];
}
