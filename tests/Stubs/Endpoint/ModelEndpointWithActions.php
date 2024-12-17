<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

class ModelEndpointWithActions extends ModelEndpoint
{
    protected static string $_RESPONSE_PROP = 'account';

    protected array $actions = ['foo' => "GET"];
}
