<?php

namespace MRussell\REST\Endpoint\Data;

use MRussell\REST\Endpoint\Interfaces\ClearableInterface;
use MRussell\REST\Endpoint\Interfaces\ArrayableInterface;
use MRussell\REST\Endpoint\Interfaces\GetInterface;
use MRussell\REST\Endpoint\Interfaces\PropertiesInterface;
use MRussell\REST\Endpoint\Interfaces\ResettableInterface;
use MRussell\REST\Endpoint\Interfaces\SetInterface;

interface DataInterface extends \ArrayAccess, PropertiesInterface, SetInterface, GetInterface, ClearableInterface,
    ResettableInterface, ArrayableInterface
{
}
