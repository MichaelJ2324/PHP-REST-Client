<?php

namespace MRussell\REST\Exception\Endpoint;

class UnknownEndpoint extends EndpointException
{
    protected $message = 'An Unknown Endpoint [%s] was requested.';

    protected $code = 404;
}
