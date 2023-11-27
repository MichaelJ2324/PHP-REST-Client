<?php

namespace MRussell\REST\Exception\Auth;

use MRussell\REST\Exception\RestClientException;

class AuthException extends RestClientException
{
    protected $message = 'Unknown Auth Exception occurred on Controller: %s';
}
