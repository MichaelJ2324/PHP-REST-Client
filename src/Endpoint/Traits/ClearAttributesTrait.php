<?php

namespace MRussell\REST\Endpoint\Traits;

trait ClearAttributesTrait
{
    use ProtectedAttributesTrait;
    /**
     * Clear the attributes array
     * @implements ClearableInterface
     */
    public function clear(): static
    {
        $this->_attributes = [];
        return $this;
    }
}
