<?php

namespace MRussell\REST\Exception\Auth;

use MRussell\REST\Exception\RestClientException;

class InvalidAuthenticationAction extends RestClientException
{
    protected $message = 'Unknown Auth Action [%s] requested on Controller: %s';
}
