<?php

namespace MRussell\REST\Exception\Endpoint;

class InvalidFileData extends EndpointException
{
    protected $message = 'Invalid file data passed to Endpoint: %s';
}
