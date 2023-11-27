<?php

/**
 * User: mrussell
 * Date: 8/15/17
 * Time: 8:50 AM
 */

namespace MRussell\REST\Tests\Auth;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Auth\BasicAuthController;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractBasicControllerTest
 * @package MRussell\REST\Tests\Auth
 * @coversDefaultClass \MRussell\REST\Auth\Abstracts\AbstractBasicController
 * @group AbstractBasicControllerTest
 */
class AbstractBasicControllerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //Add Setup for static properties here
    }

    public static function tearDownAfterClass(): void
    {
        //Add Tear Down for static properties here
    }

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers ::configureRequest
     * @covers ::getAuthHeaderValue
     */
    public function testConfigureRequest()
    {
        $Auth = new BasicAuthController();
        $Request = $Auth->configureRequest(new Request("GET", ""));
        $this->assertEquals(['Authorization' => ["Basic"]], $Request->getHeaders());
        $Auth->setCredentials([
            'username' => 'foo',
            'password' => 'bar'
        ]);
        $Request = $Auth->configureRequest($Request);
        $this->assertEquals(['Authorization' => ['Basic ' . base64_encode("foo:bar")]], $Request->getHeaders());

        $Auth = new BasicAuthController();
        $Auth->setToken(base64_encode("foo:bar"));
        $Request = $Auth->configureRequest(new Request("GET", ""));
        $this->assertEquals(['Authorization' => ['Basic ' . base64_encode("foo:bar")]], $Request->getHeaders());

    }
}
