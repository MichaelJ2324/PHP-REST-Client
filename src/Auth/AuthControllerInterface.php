<?php

namespace MRussell\REST\Auth;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface AuthControllerInterface
{
    /**
     * Get the configured Array of credentials used for authentication
     */
    public function getCredentials(): array;

    /**
     * Set the credentials used for authentication
     * @return $this
     */
    public function setCredentials(array $credentials): static;

    /**
     * Update parts of credentials used for authentication
     * @return $this
     */
    public function updateCredentials(array $credentials): static;

    /**
     * @return $this
     */
    public function setActions(array $actions): static;

    public function getActions(): array;

    /**
     * @return $this
     */
    public function setActionEndpoint(string $action, EndpointInterface $Endpoint): static;

    /**
     * Get the Endpoint configured for an action
     */
    public function getActionEndpoint(string $action): EndpointInterface;

    /**
     * Configure a provided Request with proper Authentication/Authorization
     * Used by Client HttpClient Handler Middleware
     * @return $this
     */
    public function configureRequest(Request $Request): Request;

    /**
     * Execute the authentication scheme
     */
    public function authenticate(): bool;

    /**
     * Do necessary actions to Logout
     */
    public function logout(): bool;

    /**
     * Reset the auth controller to default state. Does not call 'logout' but does clear current token/credentials
     * @return $this
     */
    public function reset(): static;

    /**
     * Is currently authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Set the current token on the Auth Controller
     * @param $token mixed
     * @return $this
     */
    public function setToken(mixed $token): static;

    /**
     * Get the current token on the Auth Controller
     */
    public function getToken(): mixed;
}
