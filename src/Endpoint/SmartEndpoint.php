<?php

namespace MRussell\REST\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint;
use MRussell\REST\Endpoint\Traits\JsonHandlerTrait;

class SmartEndpoint extends AbstractSmartEndpoint
{
    protected static $_DATA_CLASS = 'MRussell\\REST\\Endpoint\\Data\\EndpointData';
}
