<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

class ModelEndpointWithActions extends ModelEndpoint
{
    protected static $_RESPONSE_PROP = 'account';

    protected $actions = array(
        'foo' => "GET",
    );
}
