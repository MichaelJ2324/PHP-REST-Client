<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Data\AbstractEndpointData;

class DefaultedNonNullableData extends AbstractEndpointData
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::DATA_PROPERTY_DEFAULTS => ['bar' => 'foo'],
        self::DATA_PROPERTY_NULLABLE => false,
    ];
}
