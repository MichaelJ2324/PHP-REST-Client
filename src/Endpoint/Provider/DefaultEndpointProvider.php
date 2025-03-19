<?php

namespace MRussell\REST\Endpoint\Provider;

class DefaultEndpointProvider extends AbstractEndpointProvider
{
    use DefaultEndpointsTrait;

    public function __construct()
    {
        $this->registerDefaultEndpoints();
    }
}
