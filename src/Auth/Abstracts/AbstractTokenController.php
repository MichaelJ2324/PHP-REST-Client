<?php

namespace MRussell\REST\Auth\Abstracts;

use GuzzleHttp\Psr7\Request;

/**
 * Class AbstractTokenController
 * 
 * A simple token-based authentication controller for APIs that use
 * pre-configured API tokens passed via Bearer Authorization header.
 * No authenticate/logout flow required.
 * 
 * @package MRussell\REST\Auth\Abstracts
 */
abstract class AbstractTokenController extends AbstractBasicController
{
    public const DEFAULT_AUTH_TYPE = 'Bearer';

    protected string $authType = self::DEFAULT_AUTH_TYPE;

    /**
     * Token-based auth doesn't require authenticate/logout actions
     */
    protected static array $_DEFAULT_AUTH_ACTIONS = [];

    /**
     * @inheritdoc
     */
    public function setCredentials(array $credentials): static
    {
        parent::setCredentials($credentials);
        
        // If a token is provided in credentials, set it directly
        if (isset($credentials['token'])) {
            $this->setToken($credentials['token']);
        }
        
        return $this;
    }

    /**
     * Token-based auth is authenticated if a token is present
     * @inheritdoc
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->token);
    }

    /**
     * For token-based auth, authentication is always successful if token is set
     * No API call needed
     * @inheritdoc
     */
    public function authenticate(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * For token-based auth, logout just clears the token
     * No API call needed
     * @inheritdoc
     */
    public function logout(): bool
    {
        $this->clearToken();
        $this->removeCachedToken();
        return true;
    }

    /**
     * Get the Value to be set on the Auth Header
     * For token auth, just use the token directly
     */
    protected function getAuthHeaderValue(): string
    {
        return $this->authType . " " . $this->getToken();
    }
}
