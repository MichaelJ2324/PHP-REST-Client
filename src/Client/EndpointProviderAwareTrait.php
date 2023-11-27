<?php

namespace MRussell\REST\Client;

use MRussell\REST\Endpoint\Provider\EndpointProviderInterface;

trait EndpointProviderAwareTrait
{
    /**
     * @var EndpointProviderInterface
     */
    protected $EndpointProvider;

    /**
     * @inheritdoc
     * @implements EndpointProviderAwareInterface
     */
    public function setEndpointProvider(EndpointProviderInterface $EndpointProvider)
    {
        $this->EndpointProvider = $EndpointProvider;
        return $this;
    }

    /**
     * @inheritdoc
     * @implements EndpointProviderAwareInterface
     */
    public function getEndpointProvider()
    {
        return $this->EndpointProvider;
    }
}
