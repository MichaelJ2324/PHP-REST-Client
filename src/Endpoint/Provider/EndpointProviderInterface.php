<?php

namespace MRussell\REST\Endpoint\Provider;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface EndpointProviderInterface
{
    /**
     * @param string $name
     * @param string|null $version
     * @return EndpointInterface
     */
    public function getEndpoint(string $name, string $version = null): EndpointInterface;

    /**
     *
     * @param string $name
     * @param string $className
     * @param array $properties
     * @return $this
     */
    public function registerEndpoint(string $name, string $className, array $properties = []): self;

    /**
     * Check if Endpoint is registered
     * @param string $name
     * @param string|null $version
     * @return boolean
     */
    public function hasEndpoint(string $name, string $version = null): bool;
}
