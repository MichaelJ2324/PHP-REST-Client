<?php

namespace MRussell\REST\Tests\Endpoint\Event;

use MRussell\REST\Endpoint\Event\Stack;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Tests\Stubs\Endpoint\BasicEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \MRussell\REST\Endpoint\Event\Stack
 */
class StackTest extends TestCase
{
    /**
     * @covers ::getEndpoint
     * @covers ::setEndpoint
     */
    public function testGetSetEndpoint(): Stack
    {
        $stack = new Stack();
        $ep = new BasicEndpoint();
        $this->assertEquals($stack, $stack->setEndpoint($ep));
        $this->assertEquals($ep, $stack->getEndpoint());
        return $stack;
    }

    /**
     * @depends testGetSetEndpoint
     * @covers ::remove
     * @covers ::register
     * @covers ::trigger
     * @covers ::runEventHandler
     */
    public function testEvents(Stack $stack): void
    {
        $ep = $stack->getEndpoint();
        $data = [
            'foo' => 'bar',
        ];
        $id = $stack->register('foobar', function (array &$iData, EndpointInterface $endpoint) use ($ep) {
            $this->assertEquals($ep, $endpoint);
            $iData['bar'] = 'foo';
            return $iData;
        });
        $this->assertTrue(is_int($id));

        $reflected = new \ReflectionClass($stack);
        $eventsProp = $reflected->getProperty('events');
        $eventsProp->setAccessible(true);

        $this->assertArrayHasKey('foobar', $eventsProp->getValue($stack));
        $this->assertEquals($stack, $stack->trigger('foobar', $data));
        $this->assertEquals('foo', $data['bar']);

        $id2 = $stack->register('foobar', function (array &$iData, EndpointInterface $endpoint) use ($ep): array {
            $this->assertEquals($ep, $endpoint);
            unset($iData['foo']);
            return $iData;
        }, 'test');
        $this->assertEquals('test', $id2);
        $this->assertEquals(2, count($eventsProp->getValue($stack)['foobar']));

        $this->assertEquals($stack, $stack->trigger('foobar', $data));
        $this->assertEquals([
            'bar' => 'foo',
        ], $data);

        $this->assertEquals(true, $stack->remove('foobar', $id));
        $this->assertArrayHasKey('foobar', $eventsProp->getValue($stack));
        $this->assertEquals(true, $stack->remove('foobar', $id2));
        $this->assertArrayNotHasKey('foobar', $eventsProp->getValue($stack));
        $this->assertEquals(false, $stack->remove('foobar', $id2));
    }
}
