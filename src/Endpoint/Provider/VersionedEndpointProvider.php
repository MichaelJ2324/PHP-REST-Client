<?php

namespace MRussell\REST\Endpoint\Provider;

class VersionedEndpointProvider extends AbstractMultiVersionEndpointProvider
{
    /**
     * List of default endpoints to load
     */
    protected static array $_DEFAULT_ENDPOINTS = [];

    public function __construct()
    {
        foreach (static::$_DEFAULT_ENDPOINTS as $name => $epData) {
            $this->registerEndpoint($name, $epData['class'], $epData['properties'] ?? []);
        }
    }
}
