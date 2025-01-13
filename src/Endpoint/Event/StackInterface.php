<?php

namespace MRussell\REST\Endpoint\Event;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface StackInterface
{
    /**
     * Set the Endpoint for the Event Stack
     * @return $this
     */
    public function setEndpoint(EndpointInterface $endpoint): static;

    /**
     * Get the configured Endpoint for the Event Stack
     */
    public function getEndpoint(): EndpointInterface;

    /**
     * Trigger an event to run
     * @param $data
     * @return $this
     */
    public function trigger(string $event, &$data = null): static;

    /**
     * Register a new event handler
     * @param string|null $id
     */
    public function register(string $event, callable $func, string $id = null): int|string;

    /**
     * Remove an event handler
     */
    public function remove(string $event, int|string $id): bool;
}
