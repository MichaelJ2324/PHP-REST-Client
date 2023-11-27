<?php

namespace MRussell\REST\Endpoint\Event;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

interface StackInterface
{
    /**
     * Set the Endpoint for the Event Stack
     * @param EndpointInterface $endpoint
     * @return $this
     */
    public function setEndpoint(EndpointInterface $endpoint);

    /**
     * Get the configured Endpoint for the Event Stack
     * @return EndpointInterface
     */
    public function getEndpoint(): EndpointInterface;

    /**
     * Trigger an event to run
     * @param string $event
     * @param $data
     * @return $this
     */
    public function trigger(string $event, &$data = null);

    /**
     * Register a new event handler
     * @param string $event
     * @param callable $func
     * @param string|null $id
     * @return int|string
     */
    public function register(string $event, callable $func, string $id = null);

    /**
     * Remove an event handler
     * @param string $event
     * @param int|string $id
     * @return bool
     */
    public function remove(string $event, $id): bool;
}
