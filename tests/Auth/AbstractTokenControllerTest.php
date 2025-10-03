<?php

namespace MRussell\REST\Tests\Auth;

use MRussell\REST\Auth\TokenAuthController;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTokenControllerTest
 * @package MRussell\REST\Tests\Auth
 * @coversDefaultClass \MRussell\REST\Auth\Abstracts\AbstractTokenController
 * @group AbstractTokenControllerTest
 */
class AbstractTokenControllerTest extends TestCase
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
     * @covers ::setCredentials
     * @covers ::isAuthenticated
     */
    public function testSetCredentials(): void
    {
        $Auth = new TokenAuthController();
        $this->assertEquals(false, $Auth->isAuthenticated());
        
        $Auth->setCredentials(['token' => 'my-api-token-12345']);
        $this->assertEquals(true, $Auth->isAuthenticated());
        $this->assertEquals('my-api-token-12345', $Auth->getToken());
    }

    /**
     * @covers ::authenticate
     */
    public function testAuthenticate(): void
    {
        $Auth = new TokenAuthController();
        
        // Authentication should fail without token
        $this->assertEquals(false, $Auth->authenticate());
        
        // Authentication should succeed with token
        $Auth->setCredentials(['token' => 'my-api-token-12345']);
        $this->assertEquals(true, $Auth->authenticate());
    }

    /**
     * @covers ::logout
     * @covers ::isAuthenticated
     */
    public function testLogout(): void
    {
        $Auth = new TokenAuthController();
        $Auth->setCredentials(['token' => 'my-api-token-12345']);
        
        $this->assertEquals(true, $Auth->isAuthenticated());
        $this->assertEquals(true, $Auth->logout());
        $this->assertEquals(false, $Auth->isAuthenticated());
    }

    /**
     * @covers ::__construct
     */
    public function testNoAuthActions(): void
    {
        $Auth = new TokenAuthController();
        
        // Token auth should not have authenticate/logout actions
        $actions = $Auth->getActions();
        $this->assertEmpty($actions);
    }

    /**
     * Test setting token directly
     */
    public function testSetToken(): void
    {
        $Auth = new TokenAuthController();
        $Auth->setToken('direct-token-67890');
        
        $this->assertEquals(true, $Auth->isAuthenticated());
        $this->assertEquals('direct-token-67890', $Auth->getToken());
    }
    
    /**
     * @covers ::getAuthHeaderValue
     */
    public function testAuthHeaderValue(): void
    {
        $Auth = new TokenAuthController();
        $Auth->setCredentials(['token' => 'my-api-token-12345']);
        
        // Use reflection to test protected method
        $class = new \ReflectionClass($Auth);
        $method = $class->getMethod('getAuthHeaderValue');
        $method->setAccessible(true);
        
        $headerValue = $method->invoke($Auth);
        $this->assertEquals('Bearer my-api-token-12345', $headerValue);
    }
}
