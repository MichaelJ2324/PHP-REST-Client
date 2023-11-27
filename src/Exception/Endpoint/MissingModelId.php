<?php

namespace MRussell\REST\Exception\Endpoint;

class MissingModelId extends EndpointException
{
    protected $message = 'Model ID missing for current action [%s] on Endpoint: %s';
}
