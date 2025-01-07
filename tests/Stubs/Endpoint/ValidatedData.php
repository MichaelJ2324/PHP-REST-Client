<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Data\ValidatedEndpointData;

class ValidatedData extends ValidatedEndpointData
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::DATA_PROPERTY_DEFAULTS => [],
        self::DATA_PROPERTY_AUTO_VALIDATE => true,
        self::DATA_PROPERTY_REQUIRED => [
            'foo' => 'string',
            'stuff' => 'array',
        ],
        self::DATA_PROPERTY_NULLABLE => true,
    ];
}
