<?php

namespace MRussell\REST\Exception;

class RestClientException extends \Exception
{
    protected $message = 'An Unknown Exception occurred in the REST Client Framework';

    public function __construct($args = [])
    {
        if (!empty($args)) {
            if (is_string($args)) {
                $args = [$args];
            }
            $this->message = vsprintf($this->message, $args);
        }
        parent::__construct($this->message);
    }
}
