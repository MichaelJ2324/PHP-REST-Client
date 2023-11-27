<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ClearableInterface
{
    /**
     * Clear an object of data
     * @return $this
     */
    public function clear();
}
