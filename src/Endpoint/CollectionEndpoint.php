<?php

namespace MRussell\REST\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint;

class CollectionEndpoint extends AbstractCollectionEndpoint
{
    protected string $_modelInterface = ModelEndpoint::class;
}
