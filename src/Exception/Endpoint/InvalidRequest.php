<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidRequest extends EndpointException
{
    protected $message = 'Request Property not configured on Endpoint: %s';
}
