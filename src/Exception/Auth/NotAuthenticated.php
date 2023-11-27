<?php

namespace MRussell\REST\Exception\Auth;

class NotAuthenticated extends AuthException
{
    protected $message = 'Auth Controller not currently authenticated.';
}
