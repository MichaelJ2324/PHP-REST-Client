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
     * @return $this
     */
    public function set(string|array|\ArrayAccess $key, mixed $value = null): static;
}
