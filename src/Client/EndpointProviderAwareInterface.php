<?php

namespace MRussell\REST\Client;

use MRussell\REST\Endpoint\Provider\EndpointProviderInterface;

interface EndpointProviderAwareInterface
{
    /**
     * Set the Endpoint Provider that is to be used by the REST Client
     * @param EndpointProviderInterface $EndpointProvider
     * @return $this
     */
    public function setEndpointProvider(EndpointProviderInterface $EndpointProvider);

    /**
     *
     * @return EndpointProviderInterface
     */
    public function getEndpointProvider();
}
