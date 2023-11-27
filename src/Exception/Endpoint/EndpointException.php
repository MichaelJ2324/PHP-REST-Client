<?php

namespace MRussell\REST\Exception\Endpoint;

use MRussell\REST\Exception\RestClientException;

class EndpointException extends RestClientException
{
    protected $message = 'Unknown Exception occurred on Endpoint: %s';
}
