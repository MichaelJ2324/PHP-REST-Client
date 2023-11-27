<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidRegistration extends EndpointException
{
    protected $message = 'Endpoint Object [%s] must extend MRussell\REST\Endpoint\Interfaces\EndpointInterface';
}
