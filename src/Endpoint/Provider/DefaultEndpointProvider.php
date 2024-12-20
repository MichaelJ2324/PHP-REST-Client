<?php

namespace MRussell\REST\Endpoint\Provider;

class DefaultEndpointProvider extends AbstractEndpointProvider
{
    /**
     * List of default endpoints to load
     */
    protected static array $_DEFAULT_ENDPOINTS = [];

    public function __construct()
    {
        foreach (static::$_DEFAULT_ENDPOINTS as $name => $epData) {
            if (!isset($epData['properties'])) {
                $epData['properties'] = [];
            }

            $this->registerEndpoint($name, $epData['class'], $epData['properties']);
        }
    }
}
