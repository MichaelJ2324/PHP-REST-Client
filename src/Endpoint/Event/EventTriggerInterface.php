<?php

namespace MRussell\REST\Endpoint\Event;

interface EventTriggerInterface
{
    /**
     * Trigger a specific event to run
     */
    public function triggerEvent(string $event, mixed &$data = null): void;

    /**
     * Register a function to run when event is triggered
     * @param string|null $id
     */
    public function onEvent(string $event, callable $func, string $id = null): int|string;

    /**
     * Remove a registered function from the Event Stack
     * @param $id
     */
    public function offEvent(string $event, $id): bool;
}
