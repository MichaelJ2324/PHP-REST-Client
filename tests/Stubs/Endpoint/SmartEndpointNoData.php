<?php

namespace MRussell\REST\Tests\Stubs\Endpoint;

use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\Abstracts\AbstractEndpoint;
use MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint;

class SmartEndpointNoData extends AbstractSmartEndpoint
{
    protected string $_dataInterface = EndpointData::class;

    //Override constructor to prevent building out of Data Object right away
    public function __construct(array $properties = [], array $urlArgs = [])
    {
        AbstractEndpoint::__construct($properties, $urlArgs);
    }
}
