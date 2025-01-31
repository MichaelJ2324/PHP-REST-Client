<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Exception\Endpoint\UnknownModelAction;
use MRussell\REST\Exception\Endpoint\MissingModelId;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\ModelEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\ModelEndpointWithActions;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractModelEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass \MRussell\REST\Endpoint\Abstracts\AbstractModelEndpoint
 * @group AbstractModelEndpointTest
 */
class AbstractModelEndpointTest extends TestCase
{
    protected Client $client;


    protected function setUp(): void
    {
        $this->client = new Client();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        ModelEndpoint::defaultModelKey('id');
        parent::tearDown();
    }

    /**
     * @covers ::defaultModelKey
     * @covers ::getKeyProperty
     */
    public function testModelIdKey(): void
    {
        $this->assertEquals('id', ModelEndpoint::defaultModelKey());
        $this->assertEquals('key', ModelEndpoint::defaultModelKey('key'));
        $this->assertEquals('key', ModelEndpoint::defaultModelKey());
        $Model = new ModelEndpoint();
        $this->assertEquals('key', $Model->getKeyProperty());
        $this->assertEquals('id', ModelEndpoint::defaultModelKey('id'));
        $this->assertEquals('id', $Model->getKeyProperty());
        $this->assertEquals('key', ModelEndpoint::defaultModelKey('key'));
        $this->assertEquals('key', $Model->getKeyProperty());
        $this->assertEquals($Model, $Model->setProperty(ModelEndpoint::PROPERTY_MODEL_KEY, 'id'));
        $this->assertEquals('id', $Model->getKeyProperty());
        ModelEndpoint::defaultModelKey('id');
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $Model = new ModelEndpoint();
        $Class = new \ReflectionClass($Model);
        $actions = $Class->getProperty('_actions');
        $actions->setAccessible(true);
        $this->assertEquals(['create' => "POST", 'retrieve' => "GET", 'update' => "PUT", 'delete' => "DELETE"], $actions->getValue($Model));
    }

    /**
     * @covers ::__call
     * @covers ::configureAction
     */
    public function testCall(): void
    {
        $Model = new ModelEndpointWithActions();
        $Class = new \ReflectionClass($Model);
        $actions = $Class->getProperty('_actions');
        $actions->setAccessible(true);
        $this->assertEquals(['foo' => "GET", 'create' => "POST", 'retrieve' => "GET", 'update' => "PUT", 'delete' => "DELETE"], $actions->getValue($Model));

        $this->client->mockResponses->append(new Response(200));
        $Model->setClient($this->client);

        $this->assertEquals($Model, $Model->foo());
        $props = $Model->getProperties();
        $this->assertEquals("GET", $props['httpMethod']);
    }

    /**
     * @covers ::__call
     * @expectedException MRussell\REST\Exception\Endpoint\UnknownModelAction
     */
    public function testCallException(): void
    {
        $Model = new ModelEndpointWithActions();
        $this->expectException(UnknownModelAction::class);
        $this->expectExceptionMessage("Unregistered Action called on Model Endpoint [MRussell\REST\Tests\Stubs\Endpoint\ModelEndpointWithActions]: bar");
        $Model->bar();
    }

    /**
     * @covers ::__get
     * @covers ::__set
     * @covers ::__isset
     * @covers ::__unset
     * @covers ::offsetSet
     * @covers ::offsetGet
     * @covers ::offsetUnset
     * @covers ::offsetExists
     * @covers ::set
     * @covers ::get
     * @covers ::toArray
     * @covers ::reset
     * @covers ::clear
     * @covers ::set
     */
    public function testDataAccess(): void
    {
        $Model = new ModelEndpoint();
        $this->assertEquals($Model, $Model->set('foo', 'bar'));
        $this->assertEquals(true, isset($Model['foo']));
        $this->assertEquals('bar', $Model['foo']);
        $this->assertEquals(['foo' => 'bar'], $Model->toArray());
        $this->assertEquals($Model, $Model->clear());
        $this->assertEquals([], $Model->toArray());
        $Model['foo'] = 'bar';
        $this->assertEquals('bar', $Model->get('foo'));
        unset($Model['foo']);
        $this->assertEquals(false, isset($Model['foo']));
        $this->assertEquals([], $Model->toArray());

        $Model[] = ['foo' => 'bar'];
        $this->assertEquals([['foo' => 'bar']], $Model->toArray());
        $this->assertEquals($Model, $Model->set(['foo' => 'bar']));
        $this->assertEquals('bar', $Model->get('foo'));
        $this->assertEquals(['foo' => 'bar'], $Model[0]);
        $this->assertEquals($Model, $Model->reset());
        $this->assertEquals([], $Model->toArray());

        $Model->foo = 'bar';
        $Model['bar'] = 'foo';
        $this->assertEquals('bar', $Model['foo']);
        $this->assertEquals('foo', $Model->bar);
        $this->assertTrue(isset($Model->bar));
        unset($Model->bar);
        $this->assertEmpty($Model->bar);
    }

    /**
     * @covers ::setCurrentAction
     * @covers ::getCurrentAction
     */
    public function testCurrentAction(): void
    {
        $Model = new ModelEndpoint();
        $this->assertEquals($Model, $Model->setCurrentAction(ModelEndpoint::MODEL_ACTION_CREATE));
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_CREATE, $Model->getCurrentAction());
        $this->assertEquals($Model, $Model->setCurrentAction(ModelEndpoint::MODEL_ACTION_UPDATE));
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_UPDATE, $Model->getCurrentAction());
        $this->assertEquals($Model, $Model->setCurrentAction(ModelEndpoint::MODEL_ACTION_DELETE));
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_DELETE, $Model->getCurrentAction());
        $this->assertEquals($Model, $Model->setCurrentAction('foo'));
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_DELETE, $Model->getCurrentAction());
    }

    /**
     * @covers ::configurePayload
     * @covers ::configureAction
     * @covers ::retrieve
     * @covers ::configureURL
     */
    public function testRetrieve(): void
    {
        $Model = new ModelEndpoint();
        $Model->setClient($this->client);

        $this->client->mockResponses->append(new Response(200, [], json_encode([['id' => 1234]])));
        $this->assertEquals($Model, $Model->retrieve('1234'));
        $request = current($this->client->container)['request'];
        $this->assertEquals('http://phpunit.tests/account/1234', $request->getUri()->__toString());
        $this->assertEquals('1234', $Model['id']);
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_RETRIEVE, $Model->getCurrentAction());

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([['id' => 5678]])));
        $Model['id'] = '5678';
        $this->assertEquals($Model, $Model->retrieve());
        $this->assertEquals('http://phpunit.tests/account/5678', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("GET", current($this->client->container)['request']->getMethod());
        $this->assertEquals('5678', $Model->get('id'));

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([['id' => 0000]])));
        $this->assertEquals($Model, $Model->retrieve('0000'));
        $this->assertEquals('http://phpunit.tests/account/0000', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("GET", current($this->client->container)['request']->getMethod());
        $this->assertEquals('0000', $Model->get('id'));
    }

    /**
     * @covers ::retrieve
     */
    public function testMissingModelId(): void
    {
        $Model = new ModelEndpoint();
        $this->expectException(MissingModelId::class);
        $this->expectExceptionMessage("Model ID missing for current action [retrieve] on Endpoint: MRussell\REST\Tests\Stubs\Endpoint\ModelEndpoint");
        $Model->retrieve();
    }

    /**
     * @covers ::save
     * @covers ::configureAction
     * @covers ::configureURL
     * @covers ::configurePayload
     */
    public function testSave(): void
    {
        $Model = new ModelEndpoint();

        $Model->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['id' => '1234'])));
        $Model->set('foo', 'bar');

        $this->assertEquals($Model, $Model->save());
        $this->assertEquals('create', $Model->getCurrentAction());
        $this->assertEquals('http://phpunit.tests/account', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("POST", current($this->client->container)['request']->getMethod());
        $this->assertEquals('{"foo":"bar"}', current($this->client->container)['request']->getBody()->getContents());

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['id' => '1234'])));
        $Model->set('id', '1234');
        $this->assertEquals($Model, $Model->save());
        $this->assertEquals('update', $Model->getCurrentAction());
        $this->assertEquals('http://phpunit.tests/account/1234', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("PUT", current($this->client->container)['request']->getMethod());
        $this->assertEquals('{"foo":"bar","id":"1234"}', current($this->client->container)['request']->getBody()->getContents());

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['id' => '1234'])));
        $Reflected = new \ReflectionClass($Model);
        $dataProp = $Reflected->getProperty('_data');
        $dataProp->setAccessible(true);
        $dataProp->setValue($Model, null);
        $this->assertEquals($Model, $Model->save());
        $this->assertEquals('update', $Model->getCurrentAction());
        $this->assertEquals('http://phpunit.tests/account/1234', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("PUT", current($this->client->container)['request']->getMethod());
        $this->assertEquals('{"foo":"bar","id":"1234"}', current($this->client->container)['request']->getBody()->getContents());

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['id' => '1234'])));
        $dataProp->setValue($Model, ['foo' => 'baz']);
        $this->assertEquals($Model, $Model->save());
        $this->assertEquals('update', $Model->getCurrentAction());
        $this->assertEquals('http://phpunit.tests/account/1234', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("PUT", current($this->client->container)['request']->getMethod());
        $this->assertEquals('{"foo":"bar","id":"1234"}', current($this->client->container)['request']->getBody()->getContents());
    }

    /**
     * @covers ::delete
     * @covers ::configureAction
     */
    public function testDelete(): void
    {
        $Model = new ModelEndpoint();
        $Model->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([['id' => 1234]])));
        $Model->set('id', '1234');

        $this->assertEquals($Model, $Model->delete());
        $this->assertEquals(ModelEndpoint::MODEL_ACTION_DELETE, $Model->getCurrentAction());
        $this->assertEquals('http://phpunit.tests/account/1234', current($this->client->container)['request']->getUri()->__toString());
        $this->assertEquals("DELETE", current($this->client->container)['request']->getMethod());
    }

    /**
     * @covers ::setResponse
     * @covers ::parseResponse
     * @covers ::syncFromApi
     * @covers ::parseResponseBodyToArray
     */
    public function testGetResponse(): void
    {
        $Model = new ModelEndpoint();
        $Model->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([
            'id' => '1234',
            'name' => 'foo',
        ])));
        $Model->setData(['name' => 'foo']);
        $Model->save();
        $this->assertEquals("POST", current($this->client->container)['request']->getMethod());
        $this->assertEquals($Model->getResponse()->getStatusCode(), 200);
        $this->assertEquals($Model->get('id'), "1234");
        $this->assertEquals($Model->get('name'), "foo");
        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([
            'id' => '1234',
            'name' => 'foo',
            'foo' => 'bar',
        ])));
        $Model->set([
            'foo' => 'bar',
        ]);
        $Model->save();
        $this->assertEquals($Model->getResponse()->getStatusCode(), 200);
        $this->assertEquals(current($this->client->container)['request']->getMethod(), "PUT");

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode([])));
        $Model->delete();
        $this->assertEquals($Model->getResponse()->getStatusCode(), 200);
        $this->assertEquals(current($this->client->container)['request']->getMethod(), "DELETE");
        $this->assertEquals([], $Model->toArray());
        $this->assertEmpty($Model->get('id'));
    }

    /**
     * @covers ::parseResponseBodyToArray
     * @covers ::getModelResponseProp
     * @covers ::getResponseBody
     */
    public function testParseResponse(): void
    {
        $Model = new ModelEndpointWithActions();
        $Model->setClient($this->client);

        $this->client->container = [];
        $this->client->mockResponses->append(new Response(200, [], json_encode(['account' => [
            'id' => '1234',
            'name' => 'foo',
        ]])));
        $Model->setData(['name' => 'foo']);
        $Model->save();

        $Reflect = new \ReflectionClass($Model);
        $parseModelFromResponseBody = $Reflect->getMethod('parseResponseBodyToArray');
        $parseModelFromResponseBody->setAccessible(true);
        $this->assertEquals([
            'id' => '1234',
            'name' => 'foo',
        ], $parseModelFromResponseBody->invoke($Model, $Model->getResponseBody(false), $Model->getModelResponseProp()));
        $this->assertEquals([
            'id' => '1234',
            'name' => 'foo',
        ], $parseModelFromResponseBody->invoke($Model, $Model->getResponseBody(true), $Model->getModelResponseProp()));

        $Model->setProperty('response_prop', 'foobar');
        $this->assertEquals('foobar', $Model->getModelResponseProp());
        $this->assertEquals([], $parseModelFromResponseBody->invoke($Model, "foobar", $Model->getModelResponseProp()));
    }
}
