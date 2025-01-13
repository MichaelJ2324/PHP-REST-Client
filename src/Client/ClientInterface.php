<?php

namespace MRussell\REST\Client;

use GuzzleHttp\Client;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface ClientInterface
{
    public function getHttpClient(): Client;

    /**
     * Set the server on the Client, and configure the API Url if necessary
     * @param $server
     * @return $this
     */
    public function setServer(string $server): static;

    /**
     * Get the server configured on SDK Client
     */
    public function getServer(): string;

    /**
     * Get the configured API Url on the SDK Client
     */
    public function getAPIUrl(): string;

    /**
     * Set the API Version to use
     * @param $version
     * @return $this
     */
    public function setVersion(string $version): static;

    /**
     * Set the Client API Version that is to be used
     * @return string $version
     */
    public function getVersion(): string;

    /**
     * Check if Client has a given Endpoint
     */
    public function hasEndpoint(string $endpoint): bool;

    /**
     * Get an Endpoint Interface for the REST Api
     * @param string $name
     */
    public function getEndpoint(string $endpoint): EndpointInterface;

    /**
     * Get the Endpoint currently being used
     */
    public function current(): EndpointInterface|null;

    /**
     * Get the last Endpoint Used
     */
    public function last(): EndpointInterface|null;
}
