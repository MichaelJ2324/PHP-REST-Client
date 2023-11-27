<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Interfaces\ClearableInterface;

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
