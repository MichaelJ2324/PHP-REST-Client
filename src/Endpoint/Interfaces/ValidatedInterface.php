<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ValidatedInterface
{
    public function validate(): bool;
}
