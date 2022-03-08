<?php

namespace MRussell\REST\Tests\Endpoint;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\Http\Response\Standard;
use MRussell\REST\Endpoint\Abstracts\AbstractEndpoint;
use MRussell\REST\Tests\Stubs\Auth\AuthController;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\BasicEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\EndpointData;
use MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint;
use PHPUnit\Framework\TestCase;
use Sugarcrm\REST\Endpoint\Ping;

/**
 * Class AbstractEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass MRussell\REST\Endpoint\Abstracts\AbstractEndpoint
 * @group AbstractEndpointTest
 */
class AbstractEndpointTest extends TestCase {
    /**
     * @var Client
     */
    protected static $client;


    public static function setUpBeforeClass(): void {
        //Add Setup for static properties here
        self::$client = new Client();
    }

    public static function tearDownAfterClass(): void {
        //Add Tear Down for static properties here
    }

    protected $options = array(
        'foo',
        'bar'
    );

    protected $properties = array(
        'url' => '$foo/$bar/$:test'
    );

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::setUrlArgs
     * @covers ::setProperty
     * @covers ::getUrlArgs
     * @covers ::getProperties
     * @covers ::getData
     * @covers ::getRequest
     * @covers ::getResponse
     * @covers ::getBaseUrl
     * @covers ::getEndpointUrl
     */
    public function testConstructor() {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals([
            'url' => '',
            'httpMethod' => '',
            'auth' => 1
        ], $Endpoint->getProperties());
        $this->assertEquals([], $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('', $Endpoint->getEndPointUrl());
        
        $Endpoint = new BasicEndpoint($this->options);
        $this->assertEquals(array(
            'url' => '',
            'httpMethod' => '',
            'auth' => 1
        ), $Endpoint->getProperties());
        $this->assertEquals($this->options, $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('', $Endpoint->getEndPointUrl());

        $Endpoint = new BasicEndpoint($this->options, $this->properties);
        $this->assertEquals([
            'url' => '$foo/$bar/$:test',
            'httpMethod' => '',
            'auth' => 1
        ], $Endpoint->getProperties());
        $this->assertEquals($this->options, $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('$foo/$bar/$:test', $Endpoint->getEndPointUrl());
    }

    /**
     * @covers ::setUrlArgs
     * @covers ::getUrlArgs
     */
    public function testSetOptions() {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals(array(), $Endpoint->getUrlArgs());
        $this->assertEquals($Endpoint, $Endpoint->setUrlArgs($this->options));
        $this->assertEquals($this->options, $Endpoint->getUrlArgs());
        $this->assertEquals($Endpoint, $Endpoint->setUrlArgs(array()));
        $this->assertEquals(array(), $Endpoint->getUrlArgs());
    }

    /**
     * @covers ::setProperties
     * @covers ::getProperties
     * @covers ::setProperty
     */
    public function testSetProperties() {
        $Endpoint = new BasicEndpoint();
        $Endpoint->setProperties([]);
        $this->assertEquals(array(
            'url' => '',
            'httpMethod' => '',
            'auth' => 1
        ), $Endpoint->getProperties());
        $Endpoint->setProperties($this->properties);
        $props = $this->properties;
        $props['httpMethod'] = '';
        $props['auth'] = 1;
        $this->assertEquals($props, $Endpoint->getProperties());
        $Endpoint->setProperty(BasicEndpoint::PROPERTY_AUTH, true);
        $props['auth'] = true;
        $this->assertEquals($props, $Endpoint->getProperties());
    }

    /**
     * @depends testSetProperties
     * @covers ::setBaseUrl
     * @covers ::getBaseUrl
     * @covers ::getEndpointUrl
     */
    public function testSetBaseUrl() {
        $Endpoint = new BasicEndpoint();
        $Endpoint->setProperties($this->properties);
        $props = $this->properties;
        $props['httpMethod'] = '';
        $props['auth'] = 1;
        $this->assertEquals($props, $Endpoint->getProperties());
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl('localhost'));
        $this->assertEquals('localhost', $Endpoint->getBaseUrl());
        $this->assertEquals('localhost/$foo/$bar/$:test', $Endpoint->getEndPointUrl(true));
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl(""));
        $Endpoint->setClient(static::$client);
        $this->assertEquals(static::$client->getAPIUrl(), $Endpoint->getBaseUrl());
    }

    /**
     * @covers ::setData
     * @covers ::getData
     */
    public function testSetData() {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals($Endpoint, $Endpoint->setData('test'));
        $this->assertEquals('test', $Endpoint->getData());
        $this->assertEquals($Endpoint, $Endpoint->setData(null));
        $this->assertEquals(null, $Endpoint->getData());
        $this->assertEquals($Endpoint, $Endpoint->setData(array()));
        $this->assertEquals(array(), $Endpoint->getData());
        $data = new EndpointData();
        $this->assertEquals($Endpoint, $Endpoint->setData($data));
        $this->assertEquals($data, $Endpoint->getData());
    }

    /**
     * @depends testSetProperties
     * @covers ::useAuth
     */
    public function testUseAuth() {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals(1, $Endpoint->useAuth());
        $this->assertEquals($Endpoint, $Endpoint->setProperty('auth', true));
        $this->assertEquals(1, $Endpoint->useAuth());
        $this->assertEquals($Endpoint, $Endpoint->setProperty('auth', 2));
        $this->assertEquals(2, $Endpoint->useAuth());
        $this->assertEquals($Endpoint, $Endpoint->setProperty('auth', true));
        $this->assertEquals(1, $Endpoint->useAuth());
        $this->assertEquals($Endpoint, $Endpoint->setProperty('auth', false));
        $this->assertEquals(0, $Endpoint->useAuth());
    }

    /**
     * @throws \MRussell\REST\Exception\Endpoint\InvalidRequest
     * @covers ::execute
     */
    public function testInvalidRequest() {
        $Endpoint = new BasicEndpoint();
        $this->expectException(\GuzzleHttp\Exception\RequestException::class);
        $Endpoint->execute();
    }

    /**
     * @covers ::execute
     * @covers ::configureRequest
     * @covers ::setResponse
     * @covers ::configurePayload
     * @covers ::verifyUrl
     */
    public function testExecute() {
        self::$client->mockResponses->append(new Response(200));

        $Endpoint = new BasicEndpoint();
        $Endpoint->setClient(self::$client);
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl('http://localhost'));
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', 'basic'));
        $this->assertEquals($Endpoint, $Endpoint->execute());
        $this->assertEquals('http://localhost/basic', $Endpoint->buildRequest()->getUri()->__toString());
    }

    /**
     * @expectedException MRussell\REST\Exception\Endpoint\InvalidUrl
     */
    public function testInvalidUrl() {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl('http://localhost'));
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo'));
        $this->assertEquals('$foo', $Endpoint->getEndPointUrl());
        $this->assertEquals(array(), $Endpoint->getUrlArgs());
        $this->expectException(\MRussell\REST\Exception\Endpoint\InvalidUrl::class);
        $Endpoint->execute();
    }

    /**
     * @covers ::configureUrl
     *
     */
    public function testConfigureUrl() {
        $Endpoint = new BasicEndpoint();
        $Class = new \ReflectionClass('MRussell\REST\Tests\Stubs\Endpoint\BasicEndpoint');
        $method = $Class->getMethod('configureURL');
        $method->setAccessible(true);
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo'));
        $this->assertEquals('bar', $method->invoke($Endpoint, array('bar')));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar'));
        $this->assertEquals('bar/foo', $method->invoke($Endpoint, array('bar', 'foo')));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz'));
        $this->assertEquals('bar/foo', $method->invoke($Endpoint, array('bar', 'foo')));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz'));
        $this->assertEquals('bar/foo/1234', $method->invoke(
            $Endpoint,
            array(
                'foo' => 'bar',
                0 => 'foo',
                1 => 1234
            )
        ));
        $this->assertEquals('bar/foo/1234', $method->invoke(
            $Endpoint,
            array(
                'foo' => 'bar',
                3 => 'foo',
                4 => 1234
            )
        ));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz/$:foz'));
        $this->assertEquals('bar/foo/foz/1234', $method->invoke(
            $Endpoint,
            array(
                'foo' => 'bar',
                'bar' => 'foo',
                'baz' => 'foz',
                0 => 1234
            )
        ));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz/$:foz/$:aaa/$:bbb'));
        $this->assertEquals('bar/foo/foz/1234', $method->invoke(
            $Endpoint,
            array(
                'foo' => 'bar',
                'bar' => 'foo',
                'baz' => 'foz',
                0 => 1234
            )
        ));
    }

    /**
     * @covers ::getHttpClient
     * @return void
     */
    public function testHttpClient()
    {
        $Ping = new PingEndpoint();
        $client = $Ping->getHttpClient();
        $this->assertInstanceOf(\GuzzleHttp\Client::class,$client);
        $Ping->setClient(static::$client);
        $this->assertInstanceOf(\GuzzleHttp\Client::class,$Ping->getHttpClient());
        $this->assertNotEquals($client,$Ping->getHttpClient());
    }

    /**
     * @covers ::buildRequest
     * @covers ::verifyUrl
     * @covers ::configurePayload
     * @covers ::configureRequest
     * @covers ::getMethod
     * @covers ::reset
     * @covers \MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint::configureRequest
     * @return void
     */
    public function testBuildRequest()
    {
        $Ping = new PingEndpoint();
        $Ping->setClient(static::$client);
        $Ping->setData([
            'foo' => 'bar'
        ]);
        $request = $Ping->buildRequest();
        $this->assertInstanceOf(Request::class,$request);
        $this->assertEquals('http',$request->getUri()->getScheme());
        $this->assertEquals('phpunit.tests',$request->getUri()->getHost());
        $this->assertEquals('/ping',$request->getUri()->getPath());
        $this->assertEquals('foo=bar',$request->getUri()->getQuery());

        $Ping = new PingEndpoint();
        $Ping->setProperty(AbstractEndpoint::PROPERTY_HTTP_METHOD,'POST');
        $Ping->setClient(static::$client);
        $Ping->setData([
            'foo' => 'bar'
        ]);
        $request = $Ping->buildRequest();
        $this->assertInstanceOf(Request::class,$request);
        $this->assertEquals('http',$request->getUri()->getScheme());
        $this->assertEquals('phpunit.tests',$request->getUri()->getHost());
        $this->assertEquals('/ping',$request->getUri()->getPath());
        $this->assertEquals(json_encode([
            'foo' => 'bar'
        ]),$request->getBody()->getContents());

        $Ping->reset();
        $this->assertEmpty($Ping->getUrlArgs());
        $this->assertEquals('GET',$Ping->getMethod());
    }

    /**
     * @return void
     * @throws \MRussell\REST\Exception\Endpoint\InvalidDataType
     */
    public function testInvalidQueryString()
    {
        $Ping = new PingEndpoint();
        $Ping->setClient(static::$client);
        $Ping->onEvent(PingEndpoint::EVENT_CONFIGURE_PAYLOAD,function(&$data){
            $data = new \stdClass();
            $data->foo = 'bar';
        });
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query must be a string or array');
        $request = $Ping->buildRequest();
    }

    /**
     * @covers ::getResponse
     * @covers ::getResponseBody
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testGetResponse()
    {
        $Ping = new PingEndpoint();
        $Ping->setClient(static::$client);
        $pong = ['pong' => time()];
        $respBody = json_encode($pong);
        static::$client->mockResponses->append(new Response(200,[],$respBody));
        $Ping->execute();
        $this->assertInstanceOf(Response::class,$Ping->getResponse());
        $this->assertEquals($pong,$Ping->getResponseBody());
        $this->assertEmpty($Ping->getResponse()->getBody()->getContents());
    }
}
