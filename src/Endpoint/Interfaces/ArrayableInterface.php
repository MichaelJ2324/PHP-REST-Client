<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ArrayableInterface
{
    /**
     * Convert to an array
     */
    public function toArray(): array;
}
