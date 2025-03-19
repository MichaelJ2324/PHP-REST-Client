<?php

namespace MRussell\REST\Tests\Auth;

use MRussell\REST\Exception\Auth\InvalidToken;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\OAuth2Controller;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\AuthEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\RefreshEndpoint;
use PHPUnit\Framework\TestCase;
use ColinODell\PsrTestLogger\TestLogger;

/**
 * Class AbstractOAuth2ControllerTest
 * @package MRussell\REST\Tests\Auth
 * @coversDefaultClass \MRussell\REST\Auth\Abstracts\AbstractOAuth2Controller
 * @group AbstractOAuth2ControllerTest
 * @group Auth
 */
class AbstractOAuth2ControllerTest extends TestCase
{
    protected Client $client;

    protected array $token = ['access_token' => '12345', 'refresh_token' => '67890', 'expires_in' => 3600];

    protected array $credentials = ['client_id' => 'test', 'client_secret' => 's3cr3t'];

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
     * @covers ::getGrantType
     * @covers ::setGrantType
     */
    public function testSetGrantType(): void
    {
        $Auth = new OAuth2Controller();
        $this->assertEquals(OAuth2Controller::OAUTH_CLIENT_CREDENTIALS_GRANT, $Auth->getGrantType());
        $this->assertEquals($Auth, $Auth->setGrantType(OAuth2Controller::OAUTH_AUTHORIZATION_CODE_GRANT));
        $this->assertEquals(OAuth2Controller::OAUTH_AUTHORIZATION_CODE_GRANT, $Auth->getGrantType());
    }

    /**
     * @covers ::oauthHeader
     */
    public function testOAuthHeader(): void
    {
        $Auth = new OAuth2Controller();
        $this->assertEquals('Authorization', $Auth->oauthHeader());
        $this->assertEquals('Test', $Auth->oauthHeader('Test'));
        $this->assertEquals('Test', $Auth->oauthHeader());
        $Auth = new OAuth2Controller();
        $this->assertEquals('Authorization', $Auth->oauthHeader());
    }

    /**
     * @covers ::setToken
     * @covers ::cacheToken
     * @covers ::getTokenProp
     * @covers ::setTokenProp
     * @covers ::configureToken
     * @covers ::isAuthenticated
     * @covers ::isTokenExpired
     */
    public function testSetToken(): void
    {
        $Auth = new OAuth2Controller();
        $Class = new \ReflectionClass(OAuth2Controller::class);
        $isTokenExpired = $Class->getMethod('isTokenExpired');
        $isTokenExpired->setAccessible(true);
        $this->assertEquals($Auth, $Auth->setToken($this->token));
        $newToken = $Auth->getToken();
        $this->assertNotEmpty($newToken['expiration']);
        $this->assertEquals(true, ($newToken['expiration'] >= time() + 3570));
        $this->assertEquals(true, $Auth->isAuthenticated());
        $this->assertEquals($this->token['access_token'], $Auth->getTokenProp('access_token'));
        $expiration = $Auth->getTokenProp('expiration');
        $this->assertEquals($Auth, $Auth->setToken($Auth->getToken()));
        $this->assertEquals($expiration, $Auth->getTokenProp('expiration'));

        $newToken = $this->token;
        $newToken['expires_in'] = 1;
        $objToken = json_decode(json_encode($newToken));
        $this->assertEquals($Auth, $Auth->setToken($objToken));
        $this->assertNotEmpty($objToken->expiration);
        $this->assertEquals($newToken['access_token'], $Auth->getTokenProp('access_token'));
        $this->assertEquals($objToken, $Auth->getCache()->get($Auth->getCacheKey()));
        $this->assertEquals(false, $Auth->isAuthenticated());
        $this->assertEquals(true, $isTokenExpired->invoke($Auth));

        unset($newToken['expires_in']);
        unset($newToken['expiration']);
        $this->assertEquals($Auth, $Auth->setToken($newToken));
        $newToken = $Auth->getToken();
        $this->assertEquals(false, isset($newToken['expiration']));
        $this->assertEquals(null, $Auth->getTokenProp('expiration'));
        $this->assertEquals(-1, $isTokenExpired->invoke($Auth));
    }

    //    /**
    //     * @covers ::setToken
    //     * @throws InvalidToken
    //     */
    //    public function testInvalidToken(): void
    //    {
    //        $Auth = new OAuth2Controller();
    //        $this->expectException(InvalidToken::class);
    //        $Auth->setToken([]);
    //    }

    /**
     * @depends testSetToken
     * @covers ::setToken
     * @covers ::configureRequest
     * @covers ::getTokenProp
     * @covers ::getAuthHeaderValue
     */
    public function testConfigure(): void
    {
        $Auth = new OAuth2Controller();
        $Request = new Request("POST", "");
        $Auth->configureRequest($Request);
        $this->assertEquals($Auth, $Auth->setToken($this->token));
        $Request = $Auth->configureRequest($Request);
        $headers = $Request->getHeaders();
        $this->assertNotEmpty($headers['Authorization']);
        $this->assertEquals(['Bearer 12345'], $headers['Authorization']);
    }

    /**
     * @covers ::refresh
     * @covers ::setToken
     * @covers ::getTokenProp
     * @covers ::parseResponseToToken
     * @throws InvalidToken
     */
    public function testRefresh(): void
    {
        $Auth = new OAuth2Controller();
        $Logger = new TestLogger();
        $Auth->setLogger($Logger);
        $Auth->setCredentials($this->credentials);
        $this->assertEquals(false, $Auth->refresh());
        $Auth->setToken($this->token);
        $this->assertEquals(false, $Auth->refresh());
        $this->assertEquals(true, $Logger->hasDebugThatContains("Unknown Auth Action [refresh] requested on Controller"));

        $RefreshEndpoint = new RefreshEndpoint();
        $RefreshEndpoint->setClient($this->client);

        $Auth->setActionEndpoint(OAuth2Controller::ACTION_OAUTH_REFRESH, $RefreshEndpoint);

        $this->client->mockResponses->append(new Response(400, [], json_encode(['error' => 'Invalid Credentials'])));
        $Auth->setToken($this->token);
        $this->assertEquals(false, $Auth->refresh());
        $this->assertEquals(true, $Logger->hasErrorThatContains("[REST] OAuth Refresh Failed [400]"));
        $this->assertEquals(true, $Logger->hasErrorThatContains("Invalid Credentials"));

        $this->client->mockResponses->append(new Response(200, [], json_encode($this->token)));
        $Auth->setToken($this->token);
        $this->assertEquals(true, $Auth->refresh());
        $Logger->reset();
        $this->client->mockResponses->append(new Response(200, [], "}" . json_encode($this->token) . "{"));
        $Auth->setToken($this->token);
        $this->assertEquals(true, $Auth->refresh());
        $this->assertEquals(true, $Logger->hasCriticalThatContains("REST] OAuth Token Parse Exception"));
    }

    /**
     * @covers ::configureEndpoint
     * @covers ::configureRefreshEndpoint
     * @covers ::configureAuthenticationEndpoint
     */
    public function testConfigureData(): OAuth2Controller
    {
        $Auth = new OAuth2Controller();
        $Auth->setCredentials($this->credentials);
        $Auth->setToken($this->token);

        $AuthEndpoint = new AuthEndpoint();
        $AuthEndpoint->setBaseUrl('localhost');

        $Class = new \ReflectionClass(OAuth2Controller::class);
        $method = $Class->getMethod('configureEndpoint');
        $method->setAccessible(true);

        $AuthEndpoint = $method->invoke($Auth, $AuthEndpoint, OAuth2Controller::ACTION_AUTH);
        $data = $AuthEndpoint->getData();
        $this->assertEquals(OAuth2Controller::OAUTH_CLIENT_CREDENTIALS_GRANT, $data['grant_type']);

        $RefreshEndpoint = new RefreshEndpoint();
        $RefreshEndpoint->setBaseUrl('localhost');
        $RefreshEndpoint = $method->invoke($Auth, $RefreshEndpoint, OAuth2Controller::ACTION_OAUTH_REFRESH);

        $data = $RefreshEndpoint->getData();
        $this->assertEquals(OAuth2Controller::OAUTH_REFRESH_GRANT, $data['grant_type']);
        $this->assertEquals('test', $data['client_id']);
        $this->assertEquals('s3cr3t', $data['client_secret']);
        $this->assertEquals('67890', $data['refresh_token']);
        return $Auth;
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void
    {
        $Auth = new OAuth2Controller();
        $Auth->setCredentials($this->credentials);
        $Auth->setToken($this->token);
        $Auth->setGrantType(OAuth2Controller::OAUTH_AUTHORIZATION_CODE_GRANT);
        $this->assertEquals($Auth, $Auth->reset());
        $this->assertEmpty($Auth->getCredentials());
        $this->assertEmpty($Auth->getToken());
        $this->assertEquals(OAuth2Controller::OAUTH_CLIENT_CREDENTIALS_GRANT, $Auth->getGrantType());
    }

}
