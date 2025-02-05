<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Endpoint\Endpoint;
use MRussell\REST\Exception\Endpoint\InvalidRegistration;
use MRussell\REST\Exception\Endpoint\UnknownEndpoint;
use MRussell\REST\Endpoint\Provider\EndpointProviderInterface;
use MRussell\REST\Tests\Stubs\Endpoint\AuthEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\EndpointProvider;
use MRussell\REST\Tests\Stubs\Endpoint\EndpointProviderWithDefaults;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractEndpointProviderTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass \MRussell\REST\Endpoint\Provider\AbstractEndpointProvider
 * @group AbstractEndpointProviderTest
 */
class AbstractEndpointProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //Add Setup for static properties here
    }

    public static function tearDownAfterClass(): void
    {
        //Add Tear Down for static properties here
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \MRussell\REST\Endpoint\Provider\DefaultEndpointProvider::__construct
     * @covers \MRussell\REST\Endpoint\Provider\DefaultEndpointsTrait::registerDefaultEndpoints
     * @covers ::registerEndpoint
     */
    public function testConstructor(): void
    {
        $Provider = new EndpointProvider();
        $Class = new \ReflectionClass(EndpointProvider::class);
        $property = $Class->getProperty('registry');
        $property->setAccessible(true);
        $this->assertEquals([], $property->getValue($Provider));

        $Class = new \ReflectionClass(EndpointProviderWithDefaults::class);
        $property = $Class->getProperty('registry');
        $property->setAccessible(true);

        $Provider = new EndpointProviderWithDefaults();
        $this->assertNotEmpty($property->getValue($Provider));
    }

    /**
     * @covers ::registerEndpoint
     * @covers ::addEndpointRegistry
     * @return EndpointProviderInterface
     */
    public function testRegisterEndpoint(): EndpointProvider
    {
        $Provider = new EndpointProvider();
        $this->assertEquals($Provider, $Provider->registerEndpoint('auth', AuthEndpoint::class));
        $this->assertEquals($Provider, $Provider->registerEndpoint('foo', Endpoint::class, ['url' => 'foobar', 'httpMethod' => "GET"]));
        $Class = new \ReflectionClass(EndpointProvider::class);
        $property = $Class->getProperty('registry');
        $property->setAccessible(true);
        $register = $property->getValue($Provider);
        $this->assertNotEmpty($register);
        $this->assertEquals( 'auth', $register['auth']['name']);
        $this->assertTrue(isset($register['auth']['versions']));
        $this->assertTrue(isset($register['auth']['properties']));
        $this->assertEquals( 'foo', $register['foo']['name']);
        $this->assertTrue(isset($register['auth']['class']));
        $this->assertEquals( 'foobar', $register['foo']['properties']['url']);
        return $Provider;
    }

    /**
     * @depends testRegisterEndpoint
     * @covers ::registerEndpoint
     * @throws InvalidRegistration
     */
    public function testInvalidRegistration(EndpointProviderInterface $Provider): void
    {
        $this->expectException(InvalidRegistration::class);
        $this->expectExceptionMessage("Endpoint Object [baz] must extend MRussell\REST\Endpoint\Interfaces\EndpointInterface");
        $Provider->registerEndpoint("baz", "baz");
    }

    /**
     * @depends testRegisterEndpoint
     * @covers ::hasEndpoint
     * @covers ::getEndpoint
     * @covers ::buildEndpoint
     * @covers ::getEndpointDefinition
     */
    public function testGetEndpoint(EndpointProviderInterface $Provider): void
    {
        $this->assertEquals(false, $Provider->hasEndpoint('test'));
        $this->assertEquals(true, $Provider->hasEndpoint('foo'));
        $this->assertEquals(true, $Provider->hasEndpoint('auth'));
        $Auth = new AuthEndpoint();
        $this->assertEquals($Auth, $Provider->getEndpoint('auth'));
        $FooEP = $Provider->getEndpoint('foo');
        $this->assertNotEmpty($FooEP);
        $this->assertEquals('foobar', $FooEP->getEndPointUrl());
        $this->assertEquals(['url' => 'foobar', 'httpMethod' => "GET", 'auth' => 1], $FooEP->getProperties());
    }

    /**
     * @depends testRegisterEndpoint
     * @covers ::getEndpoint
     * @throws UnknownEndpoint
     */
    public function testUnknownEndpoint(EndpointProviderInterface $Provider): void
    {
        $this->expectException(UnknownEndpoint::class);
        $this->expectExceptionMessage("An Unknown Endpoint [test] was requested.");
        $Provider->getEndpoint('test');
    }
}
