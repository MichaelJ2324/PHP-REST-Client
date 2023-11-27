<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidOptions extends EndpointException
{
    protected $message = 'Invalid or missing options of Endpoint: %s';
}
