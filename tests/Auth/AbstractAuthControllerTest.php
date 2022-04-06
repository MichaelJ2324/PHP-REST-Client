<?php

namespace MRussell\REST\Tests\Auth;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Cache\MemoryCache;
use MRussell\REST\Exception\Auth\InvalidAuthenticationAction;
use MRussell\REST\Auth\Abstracts\AbstractAuthController;
use MRussell\REST\Storage\StaticStorage;
use MRussell\REST\Tests\Stubs\Auth\AuthController;
use MRussell\REST\Tests\Stubs\Client\Client;
use MRussell\REST\Tests\Stubs\Endpoint\AuthEndpoint;
use MRussell\REST\Tests\Stubs\Endpoint\LogoutEndpoint;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;

/**
 * Class AbstractAuthControllerTest
 * @package MRussell\REST\Tests\Auth\
 * @coversDefaultClass \MRussell\REST\Auth\Abstracts\AbstractAuthController
 * @group AbstractAuthControllerTest
 * @group Auth
 */
class AbstractAuthControllerTest extends TestCase {
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


    protected $authActions = array(
        AuthController::ACTION_AUTH,
        AuthController::ACTION_LOGOUT
    );

    protected $credentials = array(
        'user' => 'foo',
        'password' => 'bar'
    );

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::getActions
     * @return AuthController
     */
    public function testConstructor(): AuthController {
        $Auth = new AuthController();
        $this->assertEquals($this->authActions, $Auth->getActions());
        $actions = $this->authActions;
        $actions[] = 'test';
        $this->assertEquals($Auth, $Auth->setActions($actions));
        $this->assertEquals($actions, $Auth->getActions());
        unset($Auth);

        $Auth = new AuthController();
        $this->assertEquals($this->authActions, $Auth->getActions());
        return $Auth;
    }

    /**
     * @depends testConstructor
     * @param AuthController $Auth
     * @covers ::setCredentials
     * @covers ::getCredentials
     * @covers ::updateCredentials
     * @return AuthController
     */
    public function testSetCredentials(AuthController $Auth): AuthController {
        $this->assertEquals($Auth, $Auth->setCredentials($this->credentials));
        $this->assertEquals($this->credentials, $Auth->getCredentials());
        $this->assertEquals($Auth, $Auth->updateCredentials(array('user' => 'foobar')));
        $this->assertEquals(array(
            'user' => 'foobar',
            'password' => $this->credentials['password']
        ), $Auth->getCredentials());
        $Auth->setCredentials(array());
        $this->assertEquals(array(), $Auth->getCredentials());
        return $Auth;
    }

    /**
     * @depends testSetCredentials
     * @param AuthController $Auth
     * @covers ::setToken
     * @covers ::getToken
     * @covers ::clearToken
     * @covers ::isAuthenticated
     * @return AuthController
     */
    public function testGetToken(AuthController $Auth): AuthController {
        $this->assertEquals('12345', $Auth->getToken());
        $this->assertEquals(true, $Auth->isAuthenticated());
        $Class = new \ReflectionClass('MRussell\REST\Tests\Stubs\Auth\AuthController');
        $method = $Class->getMethod('setToken');
        $method->setAccessible(true);
        $this->assertEquals($Auth, $method->invoke($Auth, 'test'));
        $this->assertEquals('test', $Auth->getToken());
        $this->assertEquals(true, $Auth->isAuthenticated());
        $method = $Class->getMethod('clearToken');
        $method->setAccessible(true);
        $this->assertEquals($Auth, $method->invoke($Auth));
        $this->assertEquals(null, $Auth->getToken());
        $this->assertEmpty($Auth->getToken());
        $this->assertEquals(false, $Auth->isAuthenticated());
        unset($Auth);
        $Auth = new AuthController();
        $this->assertEquals('12345', $Auth->getToken());
        $this->assertEquals(true, $Auth->isAuthenticated());
        return $Auth;
    }

    /**
     * @depends testGetToken
     * @param AuthController $Auth
     * @covers ::setActions
     * @covers ::getActions
     * @covers ::getActionEndpoint
     * @covers ::setActionEndpoint
     * @return AuthController
     */
    public function testSetActions(AuthController $Auth): AuthController {
        $this->assertEquals($this->authActions, $Auth->getActions());
        $this->assertEquals($Auth, $Auth->setActions(array()));
        $this->assertEquals(array(), $Auth->getActions());
        unset($Auth);
        $Auth = new AuthController();
        $this->assertEquals($this->authActions, $Auth->getActions());
        $AuthEndpoint = new AuthEndpoint();
        $this->assertEquals($Auth, $Auth->setActionEndpoint(AbstractAuthController::ACTION_AUTH, $AuthEndpoint));
        $this->assertEquals($AuthEndpoint, $Auth->getActionEndpoint('authenticate'));
        $LogoutEndpoint = new LogoutEndpoint();
        $this->assertEquals($Auth, $Auth->setActionEndpoint(AbstractAuthController::ACTION_LOGOUT, $LogoutEndpoint));
        $this->assertEquals($LogoutEndpoint, $Auth->getActionEndpoint('logout'));

        return $Auth;
    }

    /**
     * @depends testSetActions
     * @param AuthController $Auth
     * @return void
     */
    public function testInvalidActionException(AuthController $Auth)
    {
        $this->expectExceptionMessage("Unknown Auth Action [test] requested on Controller: MRussell\REST\Auth\Abstracts\AbstractAuthController");
        $this->expectException(InvalidAuthenticationAction::class);
        $Auth->getActionEndpoint('test');
    }

    /**
     * @depends testSetActions
     * @param AuthController $Auth
     * @covers ::setCache
     * @covers ::getCache
     * @covers ::cacheToken
     * @covers ::getCacheKey
     * @covers ::getCachedToken
     * @covers ::removeCachedToken
     * @covers ::setCredentials
     * @return AuthController
     */
    public function testCaching(AuthController $Auth) {
        $cache = MemoryCache::getInstance();
        $ReflectedAuth = new \ReflectionClass($Auth);
        $cacheTokenMethod = $ReflectedAuth->getMethod('cacheToken');
        $cacheTokenMethod->setAccessible(true);
        $getCachedTokenMethod = $ReflectedAuth->getMethod('getCachedToken');
        $getCachedTokenMethod->setAccessible(true);
        $removeCachedTokenMethod = $ReflectedAuth->getMethod('removeCachedToken');
        $removeCachedTokenMethod->setAccessible(true);

        $this->assertEquals($cache, $Auth->getCache());
        $this->assertEquals($Auth, $Auth->setCache($cache));
        $token1 = $Auth->getToken();
        $this->assertEquals($Auth, $Auth->setToken($token1));
        $this->assertEquals($token1, $cache->get($Auth->getCacheKey()));
        $token2 = 'abcdefg';
        $this->assertEquals($Auth, $Auth->setToken($token2));
        $this->assertEquals($token2, $cache->get($Auth->getCacheKey()));
        $this->assertEquals(true,$cacheTokenMethod->invoke($Auth));
        $this->assertEquals($token2, $cache->get($Auth->getCacheKey()));
        $this->assertEquals(true,$removeCachedTokenMethod->invoke($Auth));
        $this->assertEquals(null, $cache->get($Auth->getCacheKey(),null));

        $cacheKey = $Auth->getCacheKey();
        $Auth->setCredentials(['username' => 'foo']);
        $this->assertNotEquals($cacheKey,$Auth->getCacheKey());
        $cacheKey = $Auth->getCacheKey();
        $this->assertEquals("AUTH_TOKEN_".sha1(json_encode(['username' => 'foo'])),$cacheKey);
        $Auth->setToken($token1);
        $this->assertEquals($token1, $getCachedTokenMethod->invoke($Auth));
        $this->assertEquals($token1, $cache->get($cacheKey));
        $Auth->clearToken();
        $Auth->setCredentials(['username' => 'foo']);
        $this->assertEquals($token1, $getCachedTokenMethod->invoke($Auth));
        $this->assertEquals($token1, $cache->get($cacheKey));
        $this->assertEquals($token1, $Auth->getToken());
    }


    /**
     * @covers ::configureEndpoint
     * @covers ::configureAuthenticationEndpoint
     * @covers ::configureLogoutEndpoint
     * @return AuthController
     */
    public function testConfigureData(): AuthController {
        $Auth = new AuthController();
        $Auth->setCredentials($this->credentials);
        $AuthEndpoint = new AuthEndpoint();
        $AuthEndpoint->setBaseUrl('localhost');
        $LogoutEndpoint = new LogoutEndpoint();
        $LogoutEndpoint->setBaseUrl('localhost');
        $Class = new \ReflectionClass('MRussell\REST\Tests\Stubs\Auth\AuthController');
        $method = $Class->getMethod('configureEndpoint');
        $method->setAccessible(true);
        $this->assertEquals($AuthEndpoint, $method->invoke($Auth, $AuthEndpoint, AbstractAuthController::ACTION_AUTH));
        $this->assertEquals($this->credentials, $AuthEndpoint->getData()->toArray());
        $this->assertEquals($LogoutEndpoint, $method->invoke($Auth, $LogoutEndpoint, AbstractAuthController::ACTION_LOGOUT));
        $this->assertEquals(array(), $LogoutEndpoint->getData());

        return $Auth;
    }

    /**
     * @param AuthController $Auth
     * @depends testConfigureData
     * @covers ::authenticate
     * @covers ::reset
     */
    public function testAuthenticate(AuthController $Auth): AuthController {
        $Endpoint = new AuthEndpoint();
        self::$client->mockResponses->append(new Response(404));
        $Endpoint->setClient(self::$client);
        $Auth->setActionEndpoint(AbstractAuthController::ACTION_AUTH, $Endpoint);
        $this->assertEquals(false, $Auth->authenticate());
        self::$client->mockResponses->append(new Response(200,[],"12345"));
        $this->assertEquals(true, $Auth->authenticate());
        $this->assertEquals("12345", $Auth->getToken());
        $this->assertEquals($Auth,$Auth->reset());
        $this->assertEmpty($Auth->getToken());
        $this->assertEmpty($Auth->getCredentials());
        return $Auth;
    }

    /**
     * @param AuthController $Auth
     * @depends testConfigureData
     * @covers ::logout
     * @covers ::getLogger
     */
    public function testLogout(AuthController $Auth): AuthController {
        $Endpoint = new LogoutEndpoint();
        $Logger = new TestLogger();
        self::$client->mockResponses->append(new Response(200));
        $Endpoint->setClient(self::$client);
        $this->assertInstanceOf(NullLogger::class,$Auth->getLogger());
        $Auth->setLogger($Logger);
        $Auth->setActionEndpoint(AbstractAuthController::ACTION_LOGOUT, $Endpoint);
        $this->assertEquals(true, $Auth->logout());
        self::$client->mockResponses->append(new Response(404));
        $this->assertEquals(false, $Auth->logout());
        $this->assertEquals(true,$Logger->hasErrorThatContains("[REST] Logout Exception"));
        return $Auth;
    }

    /**
     * @return void
     */
    public function testNoLogoutAction()
    {
        $Auth = new AuthController();
        $Logger = new TestLogger();
        $Auth->setLogger($Logger);
        $this->assertEquals(false,$Auth->logout());
        $this->assertEquals(true,$Logger->hasDebugThatContains("Unknown Auth Action [logout] requested on Controller"));
    }
}
