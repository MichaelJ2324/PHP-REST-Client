<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidDataType extends EndpointException
{
    protected $message = 'Invalid data type passed to Endpoint [%s]';
}
