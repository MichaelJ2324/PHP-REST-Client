<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ArrayableInterface
{
    /**
     * Convert to an array
     * @return array
     */
    public function toArray(): array;
}
