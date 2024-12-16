<?php

namespace MRussell\REST\Auth\Abstracts;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Class AbstractBasicController
 * @package MRussell\REST\Auth\Abstracts
 */
abstract class AbstractBasicController extends AbstractAuthController
{
    public const DEFAULT_AUTH_HEADER = 'Authorization';
    public const DEFAULT_AUTH_TYPE = 'Basic';

    protected static $_AUTH_HEADER = self::DEFAULT_AUTH_HEADER;
    protected static $_AUTH_TYPE = self::DEFAULT_AUTH_TYPE;

    /**
     * @inheritdoc
     */
    public function configureRequest(Request $Request): Request
    {
        return $Request->withHeader(static::$_AUTH_HEADER, $this->getAuthHeaderValue());
    }

    /**
     * Parse the Credentials or Token to build out the Auth Header Value
     */
    protected function getAuthHeaderValue(): string
    {
        $value = "";
        if (isset($this->credentials['username']) && isset($this->credentials['password'])) {
            $value = $this->credentials['username'] . ":" . $this->credentials['password'];
            $value = base64_encode($value);
        }
        if ($this->getToken() != null) {
            $value = $this->getToken();
        }
        return static::$_AUTH_TYPE . " " . $value;
    }
}
