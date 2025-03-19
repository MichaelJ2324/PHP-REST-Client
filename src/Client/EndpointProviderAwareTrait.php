<?php

namespace MRussell\REST\Client;

use MRussell\REST\Endpoint\Provider\EndpointProviderInterface;

trait EndpointProviderAwareTrait
{
    protected EndpointProviderInterface $endpointProvider;

    /**
     * @inheritdoc
     * @implements EndpointProviderAwareInterface
     */
    public function setEndpointProvider(EndpointProviderInterface $endpointProvider): static
    {
        $this->endpointProvider = $endpointProvider;
        return $this;
    }

    /**
     * @inheritdoc
     * @implements EndpointProviderAwareInterface
     */
    public function getEndpointProvider(): EndpointProviderInterface
    {
        return $this->endpointProvider;
    }
}
