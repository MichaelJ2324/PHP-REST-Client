<?php

namespace MRussell\REST\Endpoint\Interfaces;

use GuzzleHttp\Psr7\Response;

interface EndpointInterface extends PropertiesInterface, ResettableInterface
{
    /**
     * Set the urlArgs property to configure the URL variables
     * @return $this
     */
    public function setUrlArgs(array $args): static;

    /**
     * Get the configured Url Arguments
     */
    public function getUrlArgs(): array;

    /**
     * Sets the data on the Endpoint Object, that will be passed to Request Object
     * @return $this
     */
    public function setData(string|array|\ArrayAccess|null $data): static;

    /**
     * Get the data being used by the Endpoint
     */
    public function getData(): string|array|\ArrayAccess|null;

    /**
     * Set the Base URL that the Endpoint uses in regards to it's pre-configured Endpoint URL
     * @return $this
     */
    public function setBaseUrl(string $url): static;

    /**
     * Get the Base URL that is currently configured on the Endpoint
     */
    public function getBaseUrl(): string;

    /**
     * Get the Relative URL for the API Endpoint
     */
    public function getEndPointUrl(): string;

    /**
     * Execute the Endpoint Object using the desired action
     * @return $this
     */
    public function execute(): static;

    /**
     * Get the Response Object being used by the Endpoint
     */
    public function getResponse(): Response|null;

    /**
     * Check if authentication should be applied
     */
    public function useAuth(): int;
}
