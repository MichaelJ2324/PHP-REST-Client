<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Interfaces\SetInterface;

trait SetAttributesTrait
{
    /**
     * Set 1 or many attributes
     * @param $key
     * @param $value
     * @return $this
     * @implements SetInterface
     */
    public function set($key, $value = null)
    {
        if (is_array($key) || $key instanceof \stdClass) {
            foreach ($key as $k => $value) {
                $this->_attributes[$k] = $value;
            }
        } else {
            $this->_attributes[$key] = $value;
        }

        return $this;
    }
}
