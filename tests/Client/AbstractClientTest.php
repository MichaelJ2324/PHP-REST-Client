<?php

namespace MRussell\REST\Tests\Client;

use MRussell\REST\Exception\Client\EndpointProviderMissing;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Abstracts\AbstractEndpoint;
use MRussell\REST\Tests\Stubs\Auth\AuthController;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Client\ClientOverridenConstructor;
use MRussell\REST\Tests\Stubs\Endpoint\EndpointProviderWithDefaults;
use MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractClientTest
 * @package MRussell\REST\Tests\Client
 * @coversDefaultClass \MRussell\REST\Client\AbstractClient
 * @group AbstractClientTest
 */
class AbstractClientTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //Add Setup for static properties here
    }

    public static function tearDownAfterClass(): void
    {
        //Add Tear Down for static properties here
    }

    /**
     * @var Client
     */
    protected $Client;

    protected $server = 'localhost';

    protected $version = '1.0';

    protected function setUp(): void
    {
        $this->Client = new Client();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::initHttpClient
     * @covers ::initHttpHandlerStack
     * @covers ::getHttpClient
     * @covers ::getHandlerStack
     */
    public function testHttpClientConstructor(): void
    {
        $client = new Client();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client->getHttpClient());
        $this->assertNotEmpty($client->getHandlerStack());

        //Test that not calling parent::__construct() still allows getHttpClient and getHandlerStack() to return built out objects
        $client = new ClientOverridenConstructor();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client->getHttpClient());
        $this->assertNotEmpty($client->getHandlerStack());
    }

    /**
     * @covers ::setAuth
     * @covers ::getAuth
     * @covers ::configureAuth
     * @return Client
     */
    public function testSetAuth()
    {
        $Auth = new AuthController();
        $this->assertEquals($this->Client, $this->Client->setAuth($Auth));
        $this->assertEquals($Auth, $this->Client->getAuth());

        $handlerStack = $this->Client->getHandlerStack();
        $reflectedHandler = new \ReflectionClass($handlerStack);
        $stack = $reflectedHandler->getProperty('stack');
        $stack->setAccessible(true);

        $value = $stack->getValue($handlerStack);
        $middleware = array_filter($value, fn($item): bool => $item[1] == 'configureAuth');
        $this->assertNotEmpty($middleware);
        $middleware = current($middleware);
        $this->assertIsCallable($middleware[0]);

        return $this->Client;
    }

    /**
     * @covers ::configureAuth
     * @covers ::setHandlerStack
     * @depends testSetAuth
     */
    public function testAuthMiddleware(): void
    {
        $Auth = new AuthController();
        $this->assertEquals($this->Client, $this->Client->setAuth($Auth));
        $this->assertEquals($Auth, $this->Client->getAuth());

        $handlerStack = $this->Client->getHandlerStack();
        $reflectedHandler = new \ReflectionClass($handlerStack);
        $stack = $reflectedHandler->getProperty('stack');
        $stack->setAccessible(true);

        $value = $stack->getValue($handlerStack);
        $middleware = array_filter($value, fn($item): bool => $item[1] == 'configureAuth');
        $this->assertNotEmpty($middleware);
        $middleware = current($middleware);
        $this->assertIsCallable($middleware[0]);

        $handlerStack = HandlerStack::create(new MockHandler([]));
        $this->Client->setHandlerStack($handlerStack);

        $reflectedHandler = new \ReflectionClass($handlerStack);
        $stack = $reflectedHandler->getProperty('stack');
        $stack->setAccessible(true);

        $value = $stack->getValue($handlerStack);
        $middleware = array_filter($value, fn($item): bool => $item[1] == 'configureAuth');
        $this->assertNotEmpty($middleware);
        $middleware = current($middleware);
        $this->assertIsCallable($middleware[0]);
    }

    /**
     * Test the callable function that configures Auth on requests
     * @covers ::configureAuth
     * @group configureAuth
     */
    public function testConfigureAuth(): void
    {
        $Auth = new AuthController();
        $this->Client->setAuth($Auth);
        $this->Client->mockResponses->append(new Response(200));
        $Ping = new PingEndpoint();
        $Ping->setClient($this->Client);
        $Ping->setProperty(AbstractEndpoint::PROPERTY_AUTH, AbstractEndpoint::AUTH_NOAUTH);

        $this->Client->current($Ping);
        $Ping->execute();
        $this->assertEmpty(current($this->Client->container)['request']->getHeader('token'));
        $Ping->setProperty(AbstractEndpoint::PROPERTY_AUTH, AbstractEndpoint::AUTH_REQUIRED);
        $this->Client->container = [];
        $this->Client->mockResponses->append(new Response(200));
        $Ping->execute();
        $this->assertEquals(true, current($this->Client->container)['request']->hasHeader('token'));
    }

    /**
     * @param Client $Client
     * @covers ::setEndpointProvider
     * @covers ::getEndpointProvider
     * @return Client
     */
    public function testSetEndpointProvider()
    {
        $EndpointProvider = new EndpointProviderWithDefaults();
        $this->assertEquals($this->Client, $this->Client->setEndpointProvider($EndpointProvider));
        $this->assertEquals($EndpointProvider, $this->Client->getEndpointProvider());
        return $this->Client;
    }

    /**
     * @covers ::setServer
     * @covers ::getServer
     * @covers ::setAPIUrl
     * @covers ::getAPIUrl
     */
    public function testSetServer(): void
    {
        $this->assertEquals($this->Client, $this->Client->setServer(""));
        $this->assertEquals("", $this->Client->getServer());
        $this->assertEquals("", $this->Client->getAPIUrl());
        $this->assertEquals($this->Client, $this->Client->setServer($this->server));
        $this->assertEquals($this->server, $this->Client->getServer());
        $this->assertEquals($this->server, $this->Client->getAPIUrl());
    }

    /**
     * @covers ::setVersion
     * @covers ::getVersion
     */
    public function testSetVersion(): void
    {
        $this->assertEquals($this->Client, $this->Client->setVersion($this->version));
        $this->assertEquals($this->version, $this->Client->getVersion());
    }

    /**
     * @depends testSetEndpointProvider
     * @covers ::__call
     * @covers ::last
     * @covers ::current
     * @covers ::setCurrentEndpoint
     */
    public function testCall(Client $Client): void
    {
        $this->Client = $Client;
        $AuthEP = $this->Client->auth();
        $this->assertNotEmpty($AuthEP);
        $this->assertEquals($AuthEP, $this->Client->current());
        $AuthEP2 = $this->Client->auth();
        $this->assertNotEmpty($AuthEP2);
        $this->assertEquals($AuthEP2, $this->Client->current());
        $this->assertEquals($AuthEP, $this->Client->last());
    }

    /**
     * @throws EndpointProviderMissing
     */
    public function testProviderMissingException(): void
    {
        $this->Client = new Client();
        $this->expectException(EndpointProviderMissing::class);
        $this->expectExceptionMessage("Endpoint Provider not configured on Client object.");
        $this->Client->auth();
    }
}
