<?php

namespace MRussell\REST\Endpoint\Provider;

class DefaultEndpointProvider extends AbstractEndpointProvider
{
    /**
     * List of default endpoints to load
     * @var array
     */
    protected static $_DEFAULT_ENDPOINTS = array();

    public function __construct()
    {
        foreach (static::$_DEFAULT_ENDPOINTS as $name => $epData) {
            if (!isset($epData['properties'])) {
                $epData['properties'] = array();
            }
            $this->registerEndpoint($name, $epData['class'], $epData['properties']);
        }
    }
}
