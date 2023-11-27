<?php

namespace MRussell\REST\Exception\Client;

use MRussell\REST\Exception\RestClientException;

class ClientException extends RestClientException
{
    protected $message = 'Unknown Exception occurred on REST Client: %s';
}
