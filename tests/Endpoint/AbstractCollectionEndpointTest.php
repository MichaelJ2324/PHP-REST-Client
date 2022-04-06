<?php

namespace MRussell\REST\Tests\Endpoint;

use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\ModelEndpoint;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\CollectionEndpointWithModel;
use MRussell\REST\Tests\Stubs\Endpoint\CollectionEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\ModelEndpointWithActions;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractCollectionEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint
 * @group AbstractCollectionEndpointTest
 */
class AbstractCollectionEndpointTest extends TestCase {
    protected static $_REFLECTED_CLASS = 'MRussell\REST\Tests\Stubs\Endpoint\CollectionEndpoint';

    protected $collection = array(
        'abc123' => array(
            'id' => 'abc123',
            'name' => 'foo',
            'foo' => 'bar'
        ),
        'efg234' => array(
            'id' => 'efg234',
            'name' => 'test',
            'foo' => ''
        )
    );
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

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::setModelEndpoint
     */
    public function testConstructor() {
        $Endpoint = new CollectionEndpoint();
        $Class = new \ReflectionClass(static::$_REFLECTED_CLASS);
        $model = $Class->getProperty('model');
        $model->setAccessible(true);
        $this->assertEmpty($model->getValue($Endpoint));

        $Endpoint = new CollectionEndpointWithModel();
        $Class = new \ReflectionClass(static::$_REFLECTED_CLASS . 'WithModel');
        $model = $Class->getProperty('model');
        $model->setAccessible(true);
        $this->assertEquals('MRussell\\REST\\Tests\\Stubs\\Endpoint\\ModelEndpoint', $model->getValue($Endpoint));
    }

    /**
     * @covers ::offsetSet
     * @covers ::offsetExists
     * @covers ::offsetUnset
     * @covers ::offsetGet
     * @covers ::set
     * @covers ::toArray
     * @covers ::get
     * @covers ::buildModel
     * @covers ::clear
     * @covers ::reset
     * @covers ::at
     * @covers ::length
     */
    public function testDataAccess() {
        $Collection = new CollectionEndpointWithModel();
        $Collection[] = array(
            'foo' => 'bar',
            'abc' => 123
        );
        $this->assertEquals([[
            'foo' => 'bar',
            'abc' => 123
        ]], $Collection->toArray());
        $this->assertEquals([
            'foo' => 'bar',
            'abc' => 123
        ], $Collection[0]);
        $this->assertEquals(true, isset($Collection[0]));
        unset($Collection[0]);
        $this->assertEquals(false, isset($Collection[0]));
        $this->assertEquals(array(), $Collection->toArray());
        $this->assertEquals(0, $Collection->length());
        $this->assertEquals($Collection, $Collection->set($this->collection));
        $this->assertEquals($this->collection, $Collection->toArray());
        $this->assertEquals(array(
            'id' => 'abc123',
            'name' => 'foo',
            'foo' => 'bar'
        ), $Collection['abc123']);
        $Collection['k2r2d2'] = array(
            'id' => 'k2r2d2',
            'name' => 'Rogue One',
            'foo' => 'bar'
        );
        $this->assertEquals(array(
            'id' => 'k2r2d2',
            'name' => 'Rogue One',
            'foo' => 'bar'
        ), $Collection['k2r2d2']);
        $Model = $Collection->get('abc123');
        $Collection->setClient(static::$client);
        $this->assertEquals(true, is_object($Model));
        $this->assertEquals('bar', $Model->get('foo'));
        $Model = $Collection->get('abc123');
        $this->assertEquals(true, is_object($Model));
        $this->assertEquals(static::$client, $Model->getClient());
        $Model = $Collection->at(1);
        $this->assertEquals(array(
            'id' => 'efg234',
            'name' => 'test',
            'foo' => ''
        ), $Model->toArray());
        $Model = $Collection->at(-1);
        $this->assertEquals(array(
            'id' => 'k2r2d2',
            'name' => 'Rogue One',
            'foo' => 'bar'
        ), $Model->toArray());
        $this->assertEquals(3, $Collection->length());
        $this->assertEquals($Collection, $Collection->reset());
        $this->assertEquals(array(), $Collection->toArray());
        $this->assertEquals($Collection, $Collection->set($this->collection));
        $this->assertEquals($this->collection, $Collection->toArray());
        $this->assertEquals($Collection, $Collection->reset());
        $this->assertEquals(array(), $Collection->toArray());

        $Collection = new CollectionEndpointWithModel();
        $Collection->set($this->collection);
        $Model = $Collection->get('abc123');
        $this->assertEquals(true, is_object($Model));
        $this->assertEquals(array(
            'id' => 'abc123',
            'name' => 'foo',
            'foo' => 'bar'
        ), $Model->toArray());
    }

    /**
     * @covers ::setModelEndpoint
     */
    public function testSetModelEndpoint() {
        $Collection = new CollectionEndpointWithModel();
        $Collection->setModelEndpoint(new ModelEndpoint());
        $Class = new \ReflectionClass(static::$_REFLECTED_CLASS . "WithModel");
        $model = $Class->getProperty('model');
        $model->setAccessible(true);
        $this->assertEquals('MRussell\\REST\\Endpoint\\ModelEndpoint', $model->getValue($Collection));
        $Collection->setModelEndpoint('MRussell\\REST\\Tests\\Stubs\\Endpoint\\ModelEndpoint');
        $this->assertEquals('MRussell\\REST\\Tests\\Stubs\\Endpoint\\ModelEndpoint', $model->getValue($Collection));
    }

    /**
     * @depends testSetModelEndpoint
     * @covers ::setModelEndpoint
     * @expectedException MRussell\REST\Exception\Endpoint\UnknownEndpoint
     */
    public function testUnknownEndpoint() {
        $Collection = new CollectionEndpointWithModel();
        $this->expectException(\MRussell\REST\Exception\Endpoint\UnknownEndpoint::class);
        $this->expectExceptionMessage("An Unknown Endpoint [test] was requested.");
        $Collection->setModelEndpoint('test');

    }

    /**
     * @covers ::getEndpointUrl
     * @covers ::setProperty
     * @covers ::setBaseUrl
     */
    public function testGetEndpointUrl() {
        $Collection = new CollectionEndpointWithModel();
        $Collection->setClient(static::$client);
        $this->assertEquals('accounts', $Collection->getEndPointUrl());
        $this->assertEquals($Collection, $Collection->setProperty('url', 'foobar'));
        $this->assertEquals("foobar", $Collection->getEndPointUrl());
        $this->assertEquals("http://phpunit.tests/foobar", $Collection->getEndPointUrl(true));
        $this->assertEquals($Collection, $Collection->setBaseUrl('localhost'));
        $this->assertEquals("localhost/foobar", $Collection->getEndPointUrl(true));
        $this->assertEquals($Collection, $Collection->setProperty('url', ''));
        $this->assertEquals("localhost/accounts", $Collection->getEndPointUrl(true));

        $Collection = new CollectionEndpointWithModel();
        $Reflected = new \ReflectionClass($Collection);
        $defaultUrl = $Reflected->getProperty("_ENDPOINT_URL");
        $defaultUrl->setAccessible(true);
        $defaultUrl->setValue($Collection,"");
        $Collection->setProperty('url','');
        $this->assertEquals('account/$:id', $Collection->getEndPointUrl());

        $defaultUrl->setValue($Collection,"accounts");
    }

    /**
     * @covers ::fetch
     */
    public function testFetch() {
        $Collection = new CollectionEndpoint();
        self::$client->mockResponses->append(new Response(200));
        $Collection->setClient(self::$client);
        $Collection->fetch();
        $props = $Collection->getProperties();
        $this->assertEquals('GET', $props['httpMethod']);
    }

    /**
     * @covers ::setResponse
     * @covers ::parseResponseBodyToArray
     * @covers ::parseResponse
     * @covers ::getCollectionResponseProp
     * @covers ::syncFromApi
     */
    public function testGetResponse() {
        $Collection = new CollectionEndpoint();
        $Collection->setBaseUrl('localhost');
        $Collection->setProperty('url', 'foo');
        self::$client->mockResponses->append(new Response(200));
        $Collection->setClient(self::$client);
        $Collection->fetch();
        $Response = $Collection->getResponse();
        $this->assertEquals($Response->getStatusCode(), 200);

        self::$client->mockResponses->append(new Response(200, [], json_encode([
            [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar'
            ],
            [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar'
            ]
        ])));
        $CollectionWithModel = new CollectionEndpointWithModel();
        $CollectionWithModel->setClient(self::$client);
        $CollectionWithModel->setProperty('url', 'foo');
        $CollectionWithModel->fetch();
        $this->assertEquals([
            'test-id-1' => [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar'
            ],
            'test-id-2' => [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar'
            ]
        ], $CollectionWithModel->toArray());


        self::$client->mockResponses->append(new Response(200, [], json_encode([
            [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar'
            ],
            [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar'
            ],
            [
                'name' => 'test-no-id-name',
                'foo' => 'test-no-id-bar'
            ]
        ])));
        $CollectionWithModel = new CollectionEndpointWithModel();
        $CollectionWithModel->setClient(self::$client);
        $CollectionWithModel->setProperty('url', 'foo');
        $CollectionWithModel->fetch();
        $this->assertEquals([
            'test-id-1' => [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar'
            ],
            'test-id-2' => [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar'
            ],
            0 => [
                'name' => 'test-no-id-name',
                'foo' => 'test-no-id-bar'
            ]
        ], $CollectionWithModel->toArray());
    }

    /**
     * @covers ::parseResponseBodyToArray
     * @covers ::getResponseBody
     * @covers ::getResponseContent
     * @covers ::getCollectionResponseProp
     */
    public function testParseResponse()
    {
        $Collection = new CollectionEndpointWithModel();
        $Collection->setClient(static::$client);
        static::$client->container = [];
        static::$client->mockResponses->append(new Response(200, [], json_encode(['accounts' => array_values($this->collection)])));
        $Collection->setProperty('response_prop','accounts');
        $this->assertEquals('accounts',$Collection->getCollectionResponseProp());
        $Collection->fetch();

        $this->assertEquals($this->collection,$Collection->toArray());

        $Reflect = new \ReflectionClass($Collection);
        $parseFromResponseBody = $Reflect->getMethod('parseResponseBodyToArray');
        $parseFromResponseBody->setAccessible(true);
        $this->assertEquals(json_decode(json_encode(array_values($this->collection)),false),$parseFromResponseBody->invoke($Collection,$Collection->getResponseContent($Collection->getResponse(),false),$Collection->getCollectionResponseProp()));
        $this->assertEquals(array_values($this->collection),$parseFromResponseBody->invoke($Collection,$Collection->getResponseContent($Collection->getResponse(),true),$Collection->getCollectionResponseProp()));

        $Collection->setProperty('response_prop','foobar');
        $this->assertEquals([],$parseFromResponseBody->invoke($Collection,"foobar",$Collection->getCollectionResponseProp()));
        $Collection->setProperty('response_prop',null);
        $this->assertEquals([],$parseFromResponseBody->invoke($Collection,"foobar",$Collection->getCollectionResponseProp()));
    }

    /**
     * @covers ::current
     * @covers ::key
     * @covers ::next
     * @covers ::rewind
     * @covers ::valid
     * @return void
     */
    public function testIteratorInterface()
    {
        $Collection = new CollectionEndpointWithModel();
        $Collection->setClient(static::$client);
        static::$client->container = [];
        static::$client->mockResponses->append(new Response(200, [], json_encode(['accounts' => array_values($this->collection)])));
        $Collection->setProperty('response_prop','accounts');
        $this->assertEquals('accounts',$Collection->getCollectionResponseProp());
        $Collection->fetch();

        $this->assertEquals($this->collection,$Collection->toArray());
        foreach($Collection as $key => $value){
            $this->assertEquals(true,isset($this->collection[$key]));
            $this->assertEquals($this->collection[$key],$value);
        }
    }

    /**
     * @covers ::set
     * @covers ::reset
     * @return void
     * @depends testDataAccess
     */
    public function testModelsSet()
    {
        $Collection = new CollectionEndpoint();
        $this->assertEquals($Collection,$Collection->set($this->collection));

        $Collection = new CollectionEndpointWithModel();
        ModelEndpoint::modelIdKey('foobar');
        $this->assertEquals($Collection,$Collection->set($this->collection));
        $this->assertEquals(array_values($this->collection),$Collection->toArray());
        $Collection->reset();
        $this->assertEquals([],$Collection->toArray());
        $this->assertEquals($Collection,$Collection->set([
            new ModelEndpoint(),
            new \stdClass()
        ]));
        $this->assertEquals([
            [],
            []
        ],$Collection->toArray());
        ModelEndpoint::modelIdKey('id');
    }
}
