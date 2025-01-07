<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint;

class CollectionEndpointWithoutModel extends AbstractCollectionEndpoint
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_HTTP_METHOD => "GET",
        self::PROPERTY_URL => 'accounts',
    ];
}
