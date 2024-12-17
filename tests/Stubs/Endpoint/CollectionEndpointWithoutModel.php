<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint;

class CollectionEndpointWithoutModel extends AbstractCollectionEndpoint
{
    protected static string $_ENDPOINT_URL = 'accounts';
}
