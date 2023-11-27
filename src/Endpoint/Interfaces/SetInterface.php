<?php

namespace MRussell\REST\Endpoint\Interfaces;

/**
 * Interface for Set method functionality for managing object state
 */
interface SetInterface
{
    /**
     * Set a property or multiple attributes on an object
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value = null);
}
