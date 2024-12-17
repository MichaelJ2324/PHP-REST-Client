<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractModelEndpoint;

class ModelEndpoint extends AbstractModelEndpoint
{
    protected static string $_ENDPOINT_URL = 'account/$:id';
}
