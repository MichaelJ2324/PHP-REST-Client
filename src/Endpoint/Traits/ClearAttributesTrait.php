<?php

namespace MRussell\REST\Endpoint\Traits;

trait ClearAttributesTrait
{
    /**
     * Clear the attributes array
     * @implements ClearableInterface
     */
    public function clear()
    {
        $this->_attributes = [];
        return $this;
    }
}
