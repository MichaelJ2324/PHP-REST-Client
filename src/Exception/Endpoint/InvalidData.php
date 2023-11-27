<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidData extends EndpointException
{
    protected $message = "Missing or Invalid data on Endpoint Data. Errors: %s";
}
