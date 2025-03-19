<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ResettableInterface
{
    /**
     * Reset Object back to initial state
     * @return $this
     */
    public function reset(): static;
}
