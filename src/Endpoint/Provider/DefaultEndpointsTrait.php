<?php

namespace MRussell\REST\Endpoint\Provider;

trait DefaultEndpointsTrait
{
    /**
     * List of default endpoints to load
     */
    protected static array $_DEFAULT_ENDPOINTS = [];

    protected function registerDefaultEndpoints(): void
    {
        foreach (static::$_DEFAULT_ENDPOINTS as $i => $epData) {
            $name = $epData[AbstractEndpointProvider::ENDPOINT_NAME] ?? $i;
            $this->registerEndpoint($name, $epData[AbstractEndpointProvider::ENDPOINT_CLASS], $epData[AbstractEndpointProvider::ENDPOINT_PROPERTIES] ?? []);
        }
    }
}
