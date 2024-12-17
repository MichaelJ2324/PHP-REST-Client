<?php

namespace MRussell\REST\Endpoint\Event;

interface EventTriggerInterface
{
    /**
     * Trigger a specific event to run
     * @param mixed $data
     */
    public function triggerEvent(string $event, &$data = null): void;

    /**
     * Register a function to run when event is triggered
     * @param string|null $id
     * @return int|string
     */
    public function onEvent(string $event, callable $func, string $id = null);

    /**
     * Remove a registered function from the Event Stack
     * @param $id
     */
    public function offEvent(string $event, $id): bool;
}
