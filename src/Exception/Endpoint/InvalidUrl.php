<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidUrl extends EndpointException
{
    protected $message = 'Invalid url configuration on Endpoint [%s]: %s';
}
