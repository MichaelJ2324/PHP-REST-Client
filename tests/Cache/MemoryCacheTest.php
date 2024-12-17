<?php

namespace MRussell\REST\Tests\Cache;

use MRussell\REST\Cache\MemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * Test the MemoryCache Simple Cache implementation
 * @coversDefaultClass \MRussell\REST\Cache\MemoryCache
 */
class MemoryCacheTest extends TestCase
{
    protected function setUp(): void
    {
        MemoryCache::getInstance()->clear();
    }

    /**
     * @covers ::getInstance
     * @covers ::get
     * @covers ::set
     * @covers ::clear
     * @covers ::has
     * @covers ::delete
     */
    public function testMemoryCache()
    {
        $cache = MemoryCache::getInstance();
        $reflected = new \ReflectionClass($cache);
        $arrCache = $reflected->getProperty('cache');
        $arrCache->setAccessible(true);
        $this->assertEquals([], $arrCache->getValue($cache));

        $cache2 = MemoryCache::getInstance();
        $this->assertEquals($cache, $cache2);

        $cache2->set('foo', 'bar');
        $this->assertEquals([
            'foo' => 'bar',
        ], $arrCache->getValue($cache));
        $this->assertEquals(true, $cache->has('foo'));
        $this->assertEquals('bar', $cache2->get('foo'));
        $this->assertEquals(true, $cache->delete('foo'));
        $this->assertEquals(false, $cache->delete('foo'));
        $this->assertEquals(null, $cache2->get('foo', null));
        $this->assertEquals(true, $cache->clear());
        $this->assertEquals([], $arrCache->getValue($cache));
    }


    /**
     * @covers ::getMultiple
     * @covers ::setMultiple
     * @covers ::deleteMultiple
     * @covers ::delete
     */
    public function testMultiCache()
    {
        $cache = MemoryCache::getInstance();
        $cache->setMultiple([
            'foo' => 'bar',
            'baz' => 'foz',
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'baz' => 'foz',
        ], $cache->getMultiple(['foo','baz']));
        $this->assertEquals([
            'foo' => 'bar',
            'bar' => 'foo',
        ], $cache->getMultiple(['foo','bar'], ['bar' => 'foo']));
        $this->assertEquals(true, $cache->deleteMultiple(['foo','baz']));
        $this->assertEquals(false, $cache->deleteMultiple(['foo']));
        $this->assertEquals([], $cache->getMultiple(['foo','baz']));
    }


}
