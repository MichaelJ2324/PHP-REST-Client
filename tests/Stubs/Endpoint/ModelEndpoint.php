<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractModelEndpoint;

class ModelEndpoint extends AbstractModelEndpoint
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_URL => 'account/$:id',
    ];
}
