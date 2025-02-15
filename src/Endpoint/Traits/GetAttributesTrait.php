<?php

namespace MRussell\REST\Endpoint\Traits;

trait GetAttributesTrait
{
    use ProtectedAttributesTrait;
    /**
     * Get an attribute by Key
     * @param $key
     * @implements GetInterface
     */
    public function get(string|int $key): mixed
    {
        return $this->_attributes[$key] ?? null;
    }
}
