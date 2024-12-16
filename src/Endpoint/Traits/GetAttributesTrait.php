<?php

namespace MRussell\REST\Endpoint\Traits;

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
