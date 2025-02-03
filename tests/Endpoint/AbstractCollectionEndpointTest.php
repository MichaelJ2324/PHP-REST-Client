<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Exception\Endpoint\UnknownEndpoint;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\CollectionEndpoint;
use MRussell\REST\Endpoint\ModelEndpoint;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\CollectionEndpointWithoutModel;
use MRussell\REST\Tests\Stubs\Endpoint\ModelEndpointWithActions;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractCollectionEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass MRussell\REST\Endpoint\Abstracts\AbstractCollectionEndpoint
 * @group AbstractCollectionEndpointTest
 */
class AbstractCollectionEndpointTest extends TestCase
{
    protected static $_REFLECTED_CLASS = 'MRussell\REST\Tests\Stubs\Endpoint\CollectionEndpoint';

    protected $collection = ['abc123' => ['id' => 'abc123', 'name' => 'foo', 'foo' => 'bar'], 'efg234' => ['id' => 'efg234', 'name' => 'test', 'foo' => '']];

    protected Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers ::offsetSet
     * @covers ::offsetExists
     * @covers ::offsetUnset
     * @covers ::offsetGet
     * @covers ::getModelIdKey
     * @covers ::set
     * @covers ::toArray
     * @covers ::get
     * @covers ::buildModel
     * @covers MRussell\REST\Endpoint\Traits\GenerateEndpointTrait::generateEndpoint
     * @covers ::clear
     * @covers ::reset
     * @covers ::at
     * @covers ::length
     */
    public function testDataAccess(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $Collection[] = ['foo' => 'bar', 'abc' => 123];
        $this->assertEquals([[
            'foo' => 'bar',
            'abc' => 123,
        ]], $Collection->toArray());
        $this->assertEquals([
            'foo' => 'bar',
            'abc' => 123,
        ], $Collection[0]);
        $this->assertEquals(true, isset($Collection[0]));
        unset($Collection[0]);
        $this->assertEquals(false, isset($Collection[0]));
        $this->assertEquals([], $Collection->toArray());
        $this->assertEquals(0, $Collection->length());
        $this->assertEquals($Collection, $Collection->set($this->collection));
        $this->assertEquals($this->collection, $Collection->toArray());
        $this->assertEquals(['id' => 'abc123', 'name' => 'foo', 'foo' => 'bar'], $Collection['abc123']);
        $Collection->set([[
            'id' => 'abc123',
            'name' => 'foo',
        ]], ['merge' => true]);
        $this->assertEquals(['id' => 'abc123', 'name' => 'foo', 'foo' => 'bar'], $Collection['abc123']);
        $Collection['k2r2d2'] = ['id' => 'k2r2d2', 'name' => 'Rogue One', 'foo' => 'bar'];
        $this->assertEquals(['id' => 'k2r2d2', 'name' => 'Rogue One', 'foo' => 'bar'], $Collection['k2r2d2']);
        $Model = $Collection->get('abc123');
        $Collection->setClient($this->client);
        $this->assertEquals(false, is_object($Model));
        $Collection->setModelEndpoint(ModelEndpoint::class);
        $Model = $Collection->get('abc123');
        $this->assertEquals(true, is_object($Model));
        $this->assertEquals('bar', $Model->get('foo'));
        $this->assertEquals($this->client, $Model->getClient());
        $Model = $Collection->at(1);
        $this->assertEquals(['id' => 'efg234', 'name' => 'test', 'foo' => ''], $Model->toArray());
        $Model = $Collection->at(-1);
        $this->assertEquals(['id' => 'k2r2d2', 'name' => 'Rogue One', 'foo' => 'bar'], $Model->toArray());
        $this->assertEquals(3, $Collection->length());
        $this->assertEquals($Collection, $Collection->reset());
        $this->assertEquals([], $Collection->toArray());
        $this->assertEquals($Collection, $Collection->set($this->collection));
        $this->assertEquals($this->collection, $Collection->toArray());
        $this->assertEquals($Collection, $Collection->reset());
        $this->assertEquals([], $Collection->toArray());

        $Collection = new CollectionEndpoint();
        $Collection->set($this->collection);

        $Model = $Collection->get('abc123');
        $this->assertEquals(true, is_object($Model));
        $this->assertEquals(['id' => 'abc123', 'name' => 'foo', 'foo' => 'bar'], $Model->toArray());
    }

    /**
     * @covers ::setModelEndpoint
     */
    public function testSetModelEndpoint(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $Collection->setModelEndpoint(new ModelEndpoint());
        $this->assertEquals(ModelEndpoint::class, $Collection->getProperty('model'));
        $Collection->setModelEndpoint(\MRussell\REST\Tests\Stubs\Endpoint\ModelEndpoint::class);
        $this->assertEquals(\MRussell\REST\Tests\Stubs\Endpoint\ModelEndpoint::class, $Collection->getProperty('model'));
    }

    /**
     * @depends testSetModelEndpoint
     * @covers ::setModelEndpoint
     * @expectedException MRussell\REST\Exception\Endpoint\UnknownEndpoint
     */
    public function testUnknownEndpoint(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $this->expectException(UnknownEndpoint::class);
        $this->expectExceptionMessage("An Unknown Endpoint [test] was requested.");
        $Collection->setModelEndpoint('test');

    }

    /**
     * @covers ::getEndpointUrl
     * @covers ::setProperty
     * @covers ::setBaseUrl
     */
    public function testGetEndpointUrl(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $Collection->setClient($this->client);
        $this->assertEquals('accounts', $Collection->getEndPointUrl());
        $this->assertEquals($Collection, $Collection->setProperty('url', 'foobar'));
        $this->assertEquals("foobar", $Collection->getEndPointUrl());
        $this->assertEquals("http://phpunit.tests/foobar", $Collection->getEndPointUrl(true));
        $Collection->setModelEndpoint(ModelEndpointWithActions::class);
        $this->assertEquals("foobar", $Collection->getEndPointUrl());
        $this->assertEquals("http://phpunit.tests/foobar", $Collection->getEndPointUrl(true));

        $Collection = new CollectionEndpoint();
        $Collection->setModelEndpoint(ModelEndpointWithActions::class);
        $this->assertEquals('account/$:id', $Collection->getEndPointUrl());
    }

    /**
     * @covers ::fetch
     */
    public function testFetch(): void
    {
        $Collection = new CollectionEndpoint();
        $this->client->mockResponses->append(new Response(200));
        $Collection->setClient($this->client);
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
    public function testGetResponse(): void
    {
        $Collection = new CollectionEndpoint();
        $Collection->setBaseUrl('localhost');
        $Collection->setProperty('url', 'foo');

        $this->client->mockResponses->append(new Response(200));
        $Collection->setClient($this->client);
        $Collection->fetch();

        $Response = $Collection->getResponse();
        $this->assertEquals($Response->getStatusCode(), 200);

        $this->client->mockResponses->append(new Response(200, [], json_encode([
            [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar',
            ],
            [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar',
            ],
        ])));
        $CollectionWithModel = new CollectionEndpointWithoutModel();
        $CollectionWithModel->setClient($this->client);
        $CollectionWithModel->setProperty('url', 'foo');
        $CollectionWithModel->fetch();
        $this->assertEquals([
            'test-id-1' => [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar',
            ],
            'test-id-2' => [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar',
            ],
        ], $CollectionWithModel->toArray());


        $this->client->mockResponses->append(new Response(200, [], json_encode([
            [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar',
            ],
            [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar',
            ],
            [
                'name' => 'test-no-id-name',
                'foo' => 'test-no-id-bar',
            ],
        ])));
        $CollectionWithModel = new CollectionEndpointWithoutModel();
        $CollectionWithModel->setClient($this->client);
        $CollectionWithModel->setProperty('url', 'foo');
        $CollectionWithModel->fetch();
        $this->assertEquals([
            'test-id-1' => [
                'id' => 'test-id-1',
                'name' => 'test-id-1-name',
                'foo' => 'test-id-1-bar',
            ],
            'test-id-2' => [
                'id' => 'test-id-2',
                'name' => 'test-id-2-name',
                'foo' => 'test-id-2-bar',
            ],
            0 => [
                'name' => 'test-no-id-name',
                'foo' => 'test-no-id-bar',
            ],
        ], $CollectionWithModel->toArray());
    }

    /**
     * @covers ::parseResponseBodyToArray
     * @covers ::getResponseBody
     * @covers ::getResponseContent
     * @covers ::getCollectionResponseProp
     */
    public function testParseResponse(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $Collection->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['accounts' => array_values($this->collection)])));
        $Collection->setProperty('response_prop', 'accounts');
        $this->assertEquals('accounts', $Collection->getCollectionResponseProp());
        $Collection->fetch();

        $this->assertEquals($this->collection, $Collection->toArray());

        $Reflect = new \ReflectionClass($Collection);
        $parseFromResponseBody = $Reflect->getMethod('parseResponseBodyToArray');
        $parseFromResponseBody->setAccessible(true);
        $this->assertEquals(json_decode(json_encode(array_values($this->collection)), false), $parseFromResponseBody->invoke($Collection, $Collection->getResponseContent($Collection->getResponse(), false), $Collection->getCollectionResponseProp()));
        $this->assertEquals(array_values($this->collection), $parseFromResponseBody->invoke($Collection, $Collection->getResponseContent($Collection->getResponse(), true), $Collection->getCollectionResponseProp()));

        $Collection->setProperty('response_prop', 'foobar');
        $this->assertEquals([], $parseFromResponseBody->invoke($Collection, "foobar", $Collection->getCollectionResponseProp()));
        $Collection->setProperty('response_prop', null);
        $this->assertEquals([], $parseFromResponseBody->invoke($Collection, "foobar", $Collection->getCollectionResponseProp()));
    }

    /**
     * @covers ::current
     * @covers ::key
     * @covers ::next
     * @covers ::rewind
     * @covers ::valid
     */
    public function testIteratorInterface(): void
    {
        $Collection = new CollectionEndpointWithoutModel();
        $Collection->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['accounts' => array_values($this->collection)])));
        $Collection->setProperty('response_prop', 'accounts');
        $this->assertEquals('accounts', $Collection->getCollectionResponseProp());
        $Collection->fetch();

        $this->assertEquals($this->collection, $Collection->toArray());
        foreach ($Collection as $key => $value) {
            $this->assertEquals(true, isset($this->collection[$key]));
            $this->assertEquals($this->collection[$key], $value);
        }
    }

    /**
     * @covers ::set
     * @covers ::reset
     * @depends testDataAccess
     */
    public function testModelsSet(): void
    {
        $Collection = new CollectionEndpoint();
        $this->assertEquals($Collection, $Collection->set($this->collection));

        $Collection = new CollectionEndpointWithoutModel();
        ModelEndpoint::defaultModelKey('foobar');
        $this->assertEquals($Collection, $Collection->set($this->collection, ['reset' => true]));
        $this->assertEquals($this->collection, $Collection->toArray());
        $Collection->reset();
        $this->assertEquals([], $Collection->toArray());
        $this->assertEquals($Collection, $Collection->set([
            new ModelEndpoint(),
            new \stdClass(),
        ]));
        $this->assertEquals([
            [],
            [],
        ], $Collection->toArray());
        ModelEndpoint::defaultModelKey('id');
    }
}
