<?php

namespace MRussell\REST\Endpoint\Interfaces;

/**
 * Interface for generic Get/Set functionality for managing object state
 */
interface GetInterface
{
    /**
     * Get a specific attribute from the object
     * @param $key
     */
    public function get(string|int $key): mixed;
}
