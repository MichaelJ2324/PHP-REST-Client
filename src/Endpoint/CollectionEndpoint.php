<?php

namespace MRussell\REST\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint;

class CollectionEndpoint extends AbstractCollectionEndpoint
{
    protected static $_MODEL_CLASS = ModelEndpoint::class;
}
