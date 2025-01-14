<?php

namespace MRussell\REST\Endpoint\Provider;

class VersionedEndpointProvider extends AbstractMultiVersionEndpointProvider
{
    use DefaultEndpointsTrait;

    public function __construct()
    {
        $this->registerDefaultEndpoints();
    }
}
