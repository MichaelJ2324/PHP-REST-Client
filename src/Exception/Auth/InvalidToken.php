<?php

namespace MRussell\REST\Exception\Auth;

class InvalidToken extends AuthException
{
    protected $message = 'An Invalid Token was attempted to be set on the Auth Controller.';
}
