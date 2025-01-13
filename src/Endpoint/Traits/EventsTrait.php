<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Event\StackInterface;

trait EventsTrait
{
    protected StackInterface $eventStack;

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function triggerEvent(string $event, &$data = null): void
    {
        $this->eventStack->trigger($event, $data);
    }

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function onEvent(string $event, callable $func, string $id = null): int|string
    {
        return $this->eventStack->register($event, $func, $id);
    }

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function offEvent(string $event, $id): bool
    {
        return $this->eventStack->remove($event, $id);
    }
}
