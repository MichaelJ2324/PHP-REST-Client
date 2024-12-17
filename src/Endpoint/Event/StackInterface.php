<?php

namespace MRussell\REST\Endpoint\Event;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface StackInterface
{
    /**
     * Set the Endpoint for the Event Stack
     * @return $this
     */
    public function setEndpoint(EndpointInterface $endpoint);

    /**
     * Get the configured Endpoint for the Event Stack
     */
    public function getEndpoint(): EndpointInterface;

    /**
     * Trigger an event to run
     * @param $data
     * @return $this
     */
    public function trigger(string $event, &$data = null);

    /**
     * Register a new event handler
     * @param string|null $id
     * @return int|string
     */
    public function register(string $event, callable $func, string $id = null);

    /**
     * Remove an event handler
     * @param int|string $id
     */
    public function remove(string $event, $id): bool;
}
