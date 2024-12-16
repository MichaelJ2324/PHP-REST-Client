<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Abstracts\AbstractEndpoint;
use MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint;

class SmartEndpointNoData extends AbstractSmartEndpoint
{
    protected static $_DATA_CLASS = \MRussell\REST\Endpoint\Data\EndpointData::class;

    //Override constructor to prevent building out of Data Object right away
    public function __construct(array $urlArgs = [], array $properties = [])
    {
        AbstractEndpoint::__construct($urlArgs, $properties);
    }
}
