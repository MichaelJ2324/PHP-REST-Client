<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

class ModelEndpointWithActions extends ModelEndpoint
{
    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_URL => 'account/$:id',
        self::PROPERTY_RESPONSE_PROP => 'account',
    ];

    protected array $actions = ['foo' => "GET"];
}
