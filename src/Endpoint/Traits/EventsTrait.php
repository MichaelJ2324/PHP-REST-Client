<?php

namespace MRussell\REST\Endpoint\Traits;

use MRussell\REST\Endpoint\Event\StackInterface;

trait EventsTrait
{
    protected StackInterface $_eventStack;

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function triggerEvent(string $event, &$data = null): void
    {
        $this->_eventStack->trigger($event, $data);
    }

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function onEvent(string $event, callable $func, string $id = null): int|string
    {
        return $this->_eventStack->register($event, $func, $id);
    }

    /**
     * @abstracting EventTriggerInterface
     * @codeCoverageIgnore
     */
    public function offEvent(string $event, $id): bool
    {
        return $this->_eventStack->remove($event, $id);
    }
}
