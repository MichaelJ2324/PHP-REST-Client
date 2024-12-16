<?php

namespace MRussell\REST\Endpoint\Event;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;

class Stack implements StackInterface
{
    private static $IN_EVENT = [];

    /**
     * @var array
     */
    private $events = [];

    /**
     * @var string
     */
    protected $currentEvent;

    /**
     * @var
     */
    protected $endpoint;

    /**
     * @return $this
     */
    public function setEndpoint(EndpointInterface $endpoint): StackInterface
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getEndpoint(): EndpointInterface
    {
        return $this->endpoint;
    }

    /**
     * @param $data
     */
    public function trigger(string $event, &$data = null): StackInterface
    {
        if (array_key_exists($event, $this->events) && !array_key_exists($event, self::$IN_EVENT)) {
            $this->currentEvent = $event;
            self::$IN_EVENT[$event] = true;
            foreach ($this->events[$event] as $callable) {
                $this->runEventHandler($callable, $data);
            }
            unset(self::$IN_EVENT[$event]);
        }
        return $this;
    }

    /**
     * @param $data
     * @return void
     */
    private function runEventHandler(callable $handler, &$data = null)
    {
        $handler($data, $this->getEndpoint());
    }

    /**
     * @inheritDoc
     */
    public function register(string $event, callable $func, string $id = null)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }
        if (empty($id)) {
            $id = count($this->events);
        }
        $this->events[$event][$id] = $func;
        return $id;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $event, $id): bool
    {
        if (isset($this->events[$event]) && isset($this->events[$event][$id])) {
            unset($this->events[$event][$id]);
            if (empty($this->events[$event])) {
                unset($this->events[$event]);
            }
            return true;
        }
        return false;
    }
}
