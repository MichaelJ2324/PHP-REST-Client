<?php

namespace MRussell\REST\Exception\Endpoint;

class UnknownModelAction extends EndpointException
{
    protected $message = "Unregistered Action called on Model Endpoint [%s]: %s";
}
