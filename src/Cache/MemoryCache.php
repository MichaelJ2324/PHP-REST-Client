<?php

namespace MRussell\REST\Cache;

use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var MemoryCache
     */
    private static $instance;

    /**
     * Get the In Memory Cache Object
     *
     * @return MemoryCache
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            // @codeCoverageIgnoreStart
            static::$instance = new static();
            // @codeCoverageIgnoreEnd
        }
        return static::$instance;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache[$key] ?? $default;
    }

    /**
     * @param $key
     * @param $value
     * @param $ttl - Ignored since its in memory
     * @return bool|void
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->cache[$key] = $value;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $return = false;
        if ($this->has($key)) {
            unset($this->cache[$key]);
            $return = true;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $items = $default ?? [];
        foreach($keys as $key) {
            if ($this->has($key)) {
                $items[$key] = $this->cache[$key];
            }
        }
        if (empty($items)) {
            $items = $default;
        }
        return $items ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $return = true;
        foreach($keys as $key) {
            if (!$this->delete($key)) {
                $return = false;
            }
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }
}
