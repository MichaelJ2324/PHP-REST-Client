<?php

namespace MRussell\REST\Endpoint\Provider;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface EndpointProviderInterface
{
    public function getEndpoint(string $name, ?string $version = null): EndpointInterface;

    /**
     *
     * @return $this
     */
    public function registerEndpoint(string $name, string $className, array $properties = []): static;

    /**
     * Check if Endpoint is registered
     */
    public function hasEndpoint(string $name, ?string $version = null): bool;
}
