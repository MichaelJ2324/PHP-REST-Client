<?php

namespace MRussell\REST\Endpoint;

use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint;

class SmartEndpoint extends AbstractSmartEndpoint
{
    protected static string $_DATA_CLASS = EndpointData::class;
}
