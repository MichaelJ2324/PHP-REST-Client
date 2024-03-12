<?php

namespace MRussell\REST\Tests\Exception;

use MRussell\REST\Exception\Endpoint\EndpointException;
use MRussell\REST\Exception\RestClientException;
use PHPUnit\Framework\TestCase;

/**
 * Class RestClientExceptionTest
 * @package MRussell\REST\Tests\Exception
 * @coversDefaultClass MRussell\REST\Exception\RestClientException
 * @group RestClientExceptionTest
 */
class RestClientExceptionTest extends TestCase
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
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $Exception = new RestClientException();
        $this->assertEquals('An Unknown Exception occurred in the REST Client Framework', $Exception->getMessage());
        $Exception = new EndpointException(array('AuthEndpoint'));
        $this->assertEquals('Unknown Exception occurred on Endpoint: AuthEndpoint', $Exception->getMessage());
    }
}
