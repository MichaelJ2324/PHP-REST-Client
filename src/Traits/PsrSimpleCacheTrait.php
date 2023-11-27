<?php

namespace MRussell\REST\Traits;

use MRussell\REST\Cache\MemoryCache;
use Psr\SimpleCache\CacheInterface;

trait PsrSimpleCacheTrait
{
    /**
     * @var
     */
    protected $cache;

    /**
     * Get the Simple Cache object
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        if (empty($this->cache)) {
            $this->cache = MemoryCache::getInstance();
        }
        return $this->cache;
    }

    /**
     * Set the Simple Cache object
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
}
