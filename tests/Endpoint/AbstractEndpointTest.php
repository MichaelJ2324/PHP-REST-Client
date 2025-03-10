<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Exception\Endpoint\InvalidUrl;
use MRussell\REST\Exception\Endpoint\InvalidRequest;
use MRussell\REST\Exception\Endpoint\InvalidDataType;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Abstracts\AbstractEndpoint;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\BasicEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\DefaultedNonNullableData;
use MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class AbstractEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass \MRussell\REST\Endpoint\Abstracts\AbstractEndpoint
 * @group AbstractEndpointTest
 */
class AbstractEndpointTest extends TestCase
{
    protected Client $client;

    protected array $urlArgs = ['foo', 'bar'];

    protected array $properties = ['url' => '$foo/$bar/$:test'];

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
     * @covers ::__construct
     * @covers ::setUrlArgs
     * @covers ::setProperty
     * @covers ::getUrlArgs
     * @covers ::getProperties
     * @covers ::getData
     * @covers ::getResponse
     * @covers ::getBaseUrl
     * @covers ::getEndpointUrl
     */
    public function testConstructor(): void
    {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals([
            'url' => '',
            'httpMethod' => '',
            'auth' => 1,
        ], $Endpoint->getProperties());
        $this->assertEquals([], $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('', $Endpoint->getEndPointUrl());

        $Endpoint = new BasicEndpoint([], $this->urlArgs);
        $this->assertEquals(['url' => '', 'httpMethod' => '', 'auth' => 1], $Endpoint->getProperties());
        $this->assertEquals($this->urlArgs, $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('', $Endpoint->getEndPointUrl());

        $Endpoint = new BasicEndpoint($this->properties, $this->urlArgs);
        $this->assertEquals([
            'url' => '$foo/$bar/$:test',
            'httpMethod' => '',
            'auth' => 1,
        ], $Endpoint->getProperties());
        $this->assertEquals($this->urlArgs, $Endpoint->getUrlArgs());
        $this->assertEmpty($Endpoint->getData());
        $this->assertEmpty($Endpoint->getBaseUrl());
        $this->assertEquals('$foo/$bar/$:test', $Endpoint->getEndPointUrl());
    }

    public function testCatchNon200Responses(): void
    {
        $Endpoint = new BasicEndpoint();
        $reflection = new ReflectionClass($Endpoint);
        $catchNon200Responses = $reflection->getProperty('_catchNon200Responses');
        $catchNon200Responses->setAccessible(true);
        $this->assertFalse($catchNon200Responses->getValue($Endpoint));
        $this->assertEquals($Endpoint, $Endpoint->catchNon200Responses());
        $this->assertTrue($catchNon200Responses->getValue($Endpoint));
        $this->assertEquals($Endpoint, $Endpoint->catchNon200Responses(false));
        $this->assertFalse($catchNon200Responses->getValue($Endpoint));
        $this->assertEquals($Endpoint, $Endpoint->catchNon200Responses(true));
        $this->assertTrue($catchNon200Responses->getValue($Endpoint));
    }

    /**
     * @covers ::setUrlArgs
     * @covers ::getUrlArgs
     */
    public function testSetUrlArgs(): void
    {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals([], $Endpoint->getUrlArgs());
        $this->assertEquals($Endpoint, $Endpoint->setUrlArgs($this->urlArgs));
        $this->assertEquals($this->urlArgs, $Endpoint->getUrlArgs());
        $this->assertEquals($Endpoint, $Endpoint->setUrlArgs([]));
        $this->assertEquals([], $Endpoint->getUrlArgs());
    }

    /**
     * @covers ::setUrlArgs
     * @covers ::normalizeUrlArgs
     */
    public function testNormalizeUrlArgs(): void
    {
        $Endpoint = new BasicEndpoint();
        $Endpoint->setProperties($this->properties);
        $Endpoint->setUrlArgs($this->urlArgs);

        $normalized = $Endpoint->getUrlArgs();
        $this->assertNotEquals($this->urlArgs, $normalized);
        $this->assertEquals([
            'foo' => 'foo',
            'bar' => 'bar',
        ], $normalized);

        //Verify that appending an arg, maps to last variable
        $normalized[] = 'test';
        $urlArgs = $normalized;
        $Endpoint->setUrlArgs($normalized);
        $normalized = $Endpoint->getUrlArgs();
        $this->assertNotEquals($urlArgs, $normalized);
        $this->assertEquals([
            'foo' => 'foo',
            'bar' => 'bar',
            'test' => 'test',
        ], $normalized);

        $urlArgs = [
            'first',
            'test' => 'last',
            'middle',
        ];
        $Endpoint->setUrlArgs($urlArgs);
        $normalized = $Endpoint->getUrlArgs();
        $this->assertNotEquals($urlArgs, $normalized);
        $this->assertEquals([
            'foo' => 'first',
            'bar' => 'middle',
            'test' => 'last',
        ], $normalized);

        //Test ignoring blank args
        $urlArgs = [
            '',
            'first',
            'test' => 'last',
            'middle',
        ];
        $Endpoint->setUrlArgs($urlArgs);
        $normalized = $Endpoint->getUrlArgs();
        $this->assertNotEquals($urlArgs, $normalized);
        $this->assertEquals([
            'foo' => 'first',
            'bar' => 'middle',
            'test' => 'last',
        ], $normalized);
    }

    /**
     * @covers ::setProperties
     * @covers ::getProperties
     * @covers ::setProperty
     * @covers ::getProperty
     */
    public function testSetProperties(): void
    {
        $Endpoint = new BasicEndpoint();
        $Endpoint->setProperties([]);
        $this->assertEquals(null, $Endpoint->getProperty('foobar'));
        $this->assertEquals(['url' => '', 'httpMethod' => '', 'auth' => 1], $Endpoint->getProperties());
        $Endpoint->setProperties($this->properties);
        $props = $this->properties;
        $props['httpMethod'] = '';
        $props['auth'] = 1;
        $this->assertEquals($props, $Endpoint->getProperties());
        $Endpoint->setProperty(BasicEndpoint::PROPERTY_AUTH, true);
        $props['auth'] = true;
        $this->assertEquals($props, $Endpoint->getProperties());
        $this->assertEquals(1, $Endpoint->getProperty('auth'));
        $this->assertEquals('', $Endpoint->getProperty('httpMethod'));
    }

    /**
     * @depends testSetProperties
     * @covers ::setBaseUrl
     * @covers ::getBaseUrl
     * @covers ::getEndpointUrl
     */
    public function testSetBaseUrl(): void
    {
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
        $Endpoint->setClient($this->client);
        $this->assertEquals($this->client->getAPIUrl(), $Endpoint->getBaseUrl());
    }

    /**
     * @covers ::setData
     * @covers ::getData
     */
    public function testSetData(): void
    {
        $Endpoint = new BasicEndpoint();
        $this->assertEquals($Endpoint, $Endpoint->setData('test'));
        $this->assertEquals('test', $Endpoint->getData());
        $this->assertEquals($Endpoint, $Endpoint->setData(null));
        $this->assertEquals(null, $Endpoint->getData());
        $this->assertEquals($Endpoint, $Endpoint->setData([]));
        $this->assertEquals([], $Endpoint->getData());
        $data = new DefaultedNonNullableData();
        $this->assertEquals($Endpoint, $Endpoint->setData($data));
        $this->assertEquals($data, $Endpoint->getData());
    }

    /**
     * @depends testSetProperties
     * @covers ::useAuth
     */
    public function testUseAuth(): void
    {
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
     * @throws InvalidRequest
     * @covers ::execute
     */
    public function testInvalidRequest(): void
    {
        $Endpoint = new BasicEndpoint();
        $this->expectException(RequestException::class);
        $Endpoint->execute();
    }

    /**
     * @covers ::execute
     * @covers ::configureRequest
     * @covers ::setResponse
     * @covers ::configurePayload
     * @covers ::verifyUrl
     * @covers ::configureJsonRequest
     */
    public function testExecute(): void
    {
        $this->client->mockResponses->append(new Response(200));

        $Endpoint = new BasicEndpoint();
        $Endpoint->setClient($this->client);
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl('http://localhost'));
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', 'basic'));
        $this->assertEquals($Endpoint, $Endpoint->execute());
        $request = $this->client->mockResponses->getLastRequest();
        $this->assertEquals('http://localhost/basic', $request->getUri()->__toString());
        $this->assertEquals('application/json', $request->getHeader('Content-Type')[0]);
        $this->assertEquals('GET', $request->getMethod());

        $this->client->mockResponses->append(new Response(400));
        $Endpoint->catchNon200Responses();
        $this->assertEquals($Endpoint, $Endpoint->execute());
        $this->assertNotEmpty($Endpoint->getResponse());
        $this->assertEquals(400, $Endpoint->getResponse()->getStatusCode());
    }

    public function testInvalidUrl(): void
    {
        $this->client->mockResponses->append(new Response(200));
        $Endpoint = new BasicEndpoint();
        $Endpoint->setClient($this->client);
        $this->assertEquals($Endpoint, $Endpoint->setBaseUrl('http://localhost'));
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo'));
        $this->assertEquals('$foo', $Endpoint->getEndPointUrl());
        $this->assertEquals([], $Endpoint->getUrlArgs());
        $this->expectException(InvalidUrl::class);
        $Endpoint->execute();
    }

    /**
     * @covers ::needsUrlArgs
     * @covers ::extractUrlVariables
     */
    public function testUrlVariables(): void
    {
        $Endpoint = new BasicEndpoint();
        $Class = new \ReflectionClass(BasicEndpoint::class);
        $needsUrlArgs = $Class->getMethod('needsUrlArgs');
        $extractUrlVariables = $Class->getMethod('extractUrlVariables');
        $needsUrlArgs->setAccessible(true);
        $extractUrlVariables->setAccessible(true);

        $Endpoint->setProperty('url', 'test/$module/$:id/action/$:actionArg');
        $this->assertEquals(true, $needsUrlArgs->invoke($Endpoint));
        $variables = $extractUrlVariables->invoke($Endpoint);
        $this->assertEquals([
            'module',
            'id',
            'actionArg',
        ], array_keys($variables));

        $Endpoint->setProperty('url', 'test/$:module/$:id/$module');
        $this->assertEquals(true, $needsUrlArgs->invoke($Endpoint));
        $variables = $extractUrlVariables->invoke($Endpoint);
        $this->assertEquals([
            'module',
            'id',
        ], array_keys($variables));
    }

    /**
     * @covers ::configureUrl
     * @covers ::populateUrlWithArgs
     *
     */
    public function testConfigureUrl(): void
    {
        $Endpoint = new BasicEndpoint();
        $Class = new \ReflectionClass(BasicEndpoint::class);
        $method = $Class->getMethod('configureURL');
        $method->setAccessible(true);
        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo'));
        $this->assertEquals('bar', $method->invoke($Endpoint, ['bar']));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar'));
        $this->assertEquals('bar/foo', $method->invoke($Endpoint, ['bar', 'foo']));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz'));
        $this->assertEquals('bar/foo', $method->invoke($Endpoint, ['bar', 'foo']));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz'));
        $this->assertEquals('bar/foo/1234', $method->invoke(
            $Endpoint,
            ['foo' => 'bar', 1 => 'foo', 2 => 1234],
        ));
        $this->assertEquals('bar/foo/1234', $method->invoke(
            $Endpoint,
            ['foo' => 'bar', 3 => 'foo', 4 => 1234],
        ));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz/$:foz'));
        $this->assertEquals('bar/foo/foz/1234', $method->invoke(
            $Endpoint,
            ['foo' => 'bar', 'bar' => 'foo', 'baz' => 'foz', 0 => 1234],
        ));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', '$foo/$bar/$:baz/$:foz/$:aaa/$:bbb'));
        $this->assertEquals('bar/foo/foz/1234', $method->invoke(
            $Endpoint,
            ['foo' => 'bar', 'bar' => 'foo', 'baz' => 'foz', 0 => 1234],
        ));

        $this->assertEquals($Endpoint, $Endpoint->setProperty('url', 'test/$:module/$:id/$module'));
        $this->assertEquals('test/Accounts/1234/Accounts', $method->invoke(
            $Endpoint,
            ['Accounts','1234'],
        ));
    }

    /**
     * @covers ::getHttpClient
     * @covers ::setClient
     * @covers ::getClient
     */
    public function testHttpClient(): void
    {
        $Ping = new PingEndpoint();
        $client = $Ping->getHttpClient();
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
        $Ping->setClient($this->client);
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $Ping->getHttpClient());
        $this->assertEquals($this->client, $Ping->getClient());
        $this->assertNotEquals($client, $Ping->getHttpClient());
    }

    /**
     * @covers ::buildRequest
     * @covers ::verifyUrl
     * @covers ::configurePayload
     * @covers ::configureRequest
     * @covers ::getMethod
     * @covers ::reset
     * @covers \MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint::configureRequest
     */
    public function testBuildRequest(): void
    {
        $Ping = new PingEndpoint();
        $Ping->setClient($this->client);
        $Ping->setData([
            'foo' => 'bar',
        ]);
        $request = $Ping->buildRequest();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('http', $request->getUri()->getScheme());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('phpunit.tests', $request->getUri()->getHost());
        $this->assertEquals('/ping', $request->getUri()->getPath());
        $this->assertEquals('foo=bar', $request->getUri()->getQuery());

        $Ping = new PingEndpoint();
        $Ping->setProperty(AbstractEndpoint::PROPERTY_HTTP_METHOD, 'POST');
        $Ping->setClient($this->client);
        $Ping->setData([
            'foo' => 'bar',
        ]);
        $request = $Ping->buildRequest();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('http', $request->getUri()->getScheme());
        $this->assertEquals('phpunit.tests', $request->getUri()->getHost());
        $this->assertEquals('/ping', $request->getUri()->getPath());
        $this->assertEquals(json_encode([
            'foo' => 'bar',
        ]), $request->getBody()->getContents());

        $Ping->reset();
        $this->assertEmpty($Ping->getUrlArgs());
        $this->assertEquals('GET', $Ping->getMethod());
    }

    /**
     * @covers ::configureRequest
     */
    public function testInvalidArgumentException(): void
    {
        $Basic = new BasicEndpoint();
        $Basic->setClient($this->client);
        $Basic->setData(new DefaultedNonNullableData());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query must be a string or array');
        $Basic->buildRequest();
    }

    /**
     * @covers ::onEvent
     * @throws InvalidDataType
     */
    public function testInvalidQueryString(): void
    {
        $Ping = new PingEndpoint();
        $Ping->setClient($this->client);
        $Ping->onEvent(PingEndpoint::EVENT_CONFIGURE_PAYLOAD, function (&$data): void {
            $data = new \stdClass();
            $data->foo = 'bar';
        });
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query must be a string or array');
        $Ping->buildRequest();
    }

    /**
     * @covers ::getResponse
     * @covers ::setResponse
     * @covers ::getResponseBody
     * @covers ::getResponseContent
     * @throws GuzzleException
     */
    public function testGetResponse(): void
    {
        $Ping = new PingEndpoint();
        $Ping->setClient($this->client);

        $pong = ['pong' => time()];
        $respBody = json_encode($pong);
        $this->client->mockResponses->append(new Response(200, [], $respBody));
        $this->client->mockResponses->append(new Response(200, [], json_encode([])));

        $Ping->execute();
        $this->assertInstanceOf(Response::class, $Ping->getResponse());
        $this->assertEquals($pong, $Ping->getResponseContent($Ping->getResponse()));
        $this->assertEquals($respBody, $Ping->getResponse()->getBody()->getContents());
        $Ping->execute();
        $this->assertInstanceOf(Response::class, $Ping->getResponse());
        $this->assertEquals([], $Ping->getResponseBody());
        $this->assertEquals("[]", $Ping->getResponse()->getBody()->getContents());
    }


    /**
     * @covers ::asyncExecute
     * @covers ::getPromise
     */
    public function testAsyncExecute(): void
    {
        $Ping = new PingEndpoint();
        $Ping->setClient($this->client);

        $pong = ['pong' => time()];
        $respBody = json_encode($pong);
        $this->client->mockResponses->append(new Response(200, [], $respBody));
        $this->client->mockResponses->append(new Response(401, [], json_encode(['error' => 'invalid_data'])));

        $self = $this;
        $promises = [];
        $promises['first'] = $Ping->asyncExecute(['success' => function (Response $resp) use ($self, $respBody): void {
            $self->assertInstanceOf(Response::class, $resp);
            $self->assertEquals($respBody, $resp->getBody()->getContents());
        }])->getPromise();
        $promises['second'] = $Ping->asyncExecute(['error' => function (RequestException $exception) use ($self): void {
            $self->assertInstanceOf(RequestException::class, $exception);
            $self->assertInstanceOf(Response::class, $exception->getResponse());
            $self->assertEquals(json_encode(['error' => 'invalid_data']), $exception->getResponse()->getBody()->getContents());
        }])->getPromise();
        $this->assertInstanceOf(Promise::class, $promises['first']);
        $this->assertInstanceOf(Promise::class, $promises['second']);

        $response = $promises['first']->wait();
        $this->assertInstanceOf(Response::class, $response);
        try {
            $response = $promises['second']->wait();
        } catch (\Exception $exception) {
            $self->assertInstanceOf(RequestException::class, $exception);
            $self->assertInstanceOf(Response::class, $exception->getResponse());
        }
    }
}
