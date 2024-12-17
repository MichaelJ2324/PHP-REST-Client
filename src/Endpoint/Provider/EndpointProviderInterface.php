<?php

namespace MRussell\REST\Endpoint\Provider;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface EndpointProviderInterface
{
    /**
     * @param string|null $version
     */
    public function getEndpoint(string $name, string $version = null): EndpointInterface;

    /**
     *
     * @return $this
     */
    public function registerEndpoint(string $name, string $className, array $properties = []): self;

    /**
     * Check if Endpoint is registered
     * @param string|null $version
     */
    public function hasEndpoint(string $name, string $version = null): bool;
}
