<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Interfaces\GetInterface;

trait GetAttributesTrait
{
    /**
     * Get an attribute by Key
     * @param $key
     * @return mixed
     * @implements GetInterface
     */
    public function get($key)
    {
        return $this->_attributes[$key] ?? null;
    }
}
