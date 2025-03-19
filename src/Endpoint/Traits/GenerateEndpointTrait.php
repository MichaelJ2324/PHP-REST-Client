<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

trait GenerateEndpointTrait
{
    protected function generateEndpoint(string $endpoint): EndpointInterface|null
    {
        $EP = null;
        if (class_exists($endpoint)) {
            $EP = new $endpoint();
        } elseif (!empty($this->_client)) {
            if ($this->_client->hasEndpoint($endpoint)) {
                $EP = $this->_client->getEndpoint($endpoint);
            }
        }

        if ($EP instanceof EndpointInterface) {
            if (!empty($this->_client)) {
                $EP->setClient($this->getClient());
            } else {
                $EP->setBaseUrl($this->getBaseUrl());
            }
        }

        return $EP;
    }
}
