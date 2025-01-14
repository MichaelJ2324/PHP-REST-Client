<?php

namespace MRussell\REST\Endpoint\Traits;

trait SetAttributesTrait
{
    use ProtectedAttributesTrait;
    /**
     * Set 1 or many attributes
     * @param $key
     * @param $value
     * @return $this
     * @implements SetInterface
     */
    public function set(string|array|\ArrayAccess $key, mixed $value = null): static
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
