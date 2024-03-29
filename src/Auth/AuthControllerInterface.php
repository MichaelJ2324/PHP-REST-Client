<?php

namespace MRussell\REST\Auth;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Storage\StorageControllerInterface;

interface AuthControllerInterface
{
    /**
     * Get the configured Array of credentials used for authentication
     * @return array
     */
    public function getCredentials(): array;

    /**
     * Set the credentials used for authentication
     * @param array $credentials
     * @return $this
     */
    public function setCredentials(array $credentials);

    /**
     * Update parts of credentials used for authentication
     * @param array $credentials
     * @return $this
     */
    public function updateCredentials(array $credentials);

    /**
     * @param array $actions
     * @return $this
     */
    public function setActions(array $actions);

    /**
     * @return array
     */
    public function getActions(): array;

    /**
     * @param string $action
     * @param EndpointInterface $Endpoint
     * @return $this
     */
    public function setActionEndpoint(string $action, EndpointInterface $Endpoint);

    /**
     * Get the Endpoint configured for an action
     * @param string $action
     * @return EndpointInterface
     */
    public function getActionEndpoint(string $action): EndpointInterface;

    /**
     * Configure a provided Request with proper Authentication/Authorization
     * Used by Client HttpClient Handler Middleware
     * @param Request $Request
     * @return $this
     */
    public function configureRequest(Request $Request): Request;

    /**
     * Execute the authentication scheme
     * @return boolean
     */
    public function authenticate(): bool;

    /**
     * Do necessary actions to Logout
     * @return boolean
     */
    public function logout(): bool;

    /**
     * Reset the auth controller to default state. Does not call 'logout' but does clear current token/credentials
     * @return $this
     */
    public function reset();

    /**
     * Is currently authenticated
     * @return boolean
     */
    public function isAuthenticated(): bool;

    /**
     * Set the current token on the Auth Controller
     * @param $token mixed
     * @return $this
     */
    public function setToken($token);

    /**
     * Get the current token on the Auth Controller
     * @return mixed
     */
    public function getToken();
}
