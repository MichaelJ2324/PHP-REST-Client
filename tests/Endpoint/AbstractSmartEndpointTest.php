<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\SmartEndpoint;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Tests\Stubs\Endpoint\SmartEndpointNoData;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class AbstractSmartEndpointTest
 * @package MRussell\REST\Tests\Endpoint
 * @coversDefaultClass MRussell\REST\Endpoint\Abstracts\AbstractSmartEndpoint
 * @group AbstractSmartEndpointTest
 */
class AbstractSmartEndpointTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //Add Setup for static properties here
    }

    public static function tearDownAfterClass(): void
    {
        //Add Tear Down for static properties here
    }

    protected $properties = [
        'data' => [
            'required' => [
                'foo' => 'string'
            ],
            'defaults' => [
                'bar' => 'foo'
            ]
        ]
    ];

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
     * @covers ::configureDataProperties
     */
    public function testConstructor()
    {
        $Endpoint = new SmartEndpointNoData();
        $this->assertNotEmpty($Endpoint->getData());
        $Endpoint = new SmartEndpointNoData(['foo'], ['url' => 'bar']);
        $this->assertNotEmpty($Endpoint->getData());
        $this->assertEquals($Endpoint->getEndPointUrl(), 'bar');
        $this->assertEquals($Endpoint->getUrlArgs(), ['foo']);
        $Endpoint = new SmartEndpoint(
            ['foo'],
            $this->properties
        );
        $this->assertNotEmpty($Endpoint->getData());
        $this->assertEquals($Endpoint->getUrlArgs(), ['foo']);
        $this->assertEquals($Endpoint->getUrlArgs(), ['foo']);
        $this->assertEquals($Endpoint->getData()->toArray(), ['bar' => 'foo']);
        $this->assertEquals($Endpoint->getProperties()['data'], $this->properties['data']);
    }

    /**
     * @covers ::setProperties
     * @covers ::setProperty
     * @covers ::configureDataProperties
     * @covers ::setProperty
     */
    public function testSetProperties()
    {
        $Endpoint = new SmartEndpoint();
        $Endpoint->setProperties([]);
        $this->assertEquals(array(
            'url' => '',
            'httpMethod' => '',
            'auth' => 1,
            'data' => [
                'required' => [],
                'defaults' => []
            ]
        ), $Endpoint->getProperties());

        $this->assertEquals([
            'required' => [],
            'defaults' => []
        ], $Endpoint->getData()->getProperties());

        $Endpoint->setProperties($this->properties);
        $this->assertEquals([
            'url' => '',
            'httpMethod' => '',
            'auth' => 1,
            'data' => [
                'required' => [
                    'foo' => 'string'
                ],
                'defaults' => [
                    'bar' => 'foo'
                ]
            ]
        ], $Endpoint->getProperties());

        $this->assertEquals([
            'required' => [
                'foo' => 'string'
            ],
            'defaults' => [
                'bar' => 'foo'
            ]
        ], $Endpoint->getData()->getProperties());

        $this->assertEquals($Endpoint, $Endpoint->setProperty('data', [
            'required' => [
                'foo' => 'string'
            ],
            'defaults' => [
            ]
        ]));
        $this->assertEquals([
            'required' => [
                'foo' => 'string'
            ],
            'defaults' => [
            ]
        ], $Endpoint->getData()->getProperties());
    }

    /**
     * @covers ::setData
     * @covers ::getData
     * @covers ::configurePayload
     */
    public function testSetData()
    {
        $Endpoint = new SmartEndpointNoData();
        $this->assertEquals($Endpoint, $Endpoint->setData(null));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $Endpoint = new SmartEndpointNoData();
        $this->assertEquals($Endpoint, $Endpoint->setData([]));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $Data = new EndpointData();
        $this->assertEquals($Endpoint, $Endpoint->setData($Data));
        $this->assertEquals([
            'required' => [],
            'defaults' => []
        ], $Endpoint->getData()->getProperties());

        $this->assertEquals($Endpoint, $Endpoint->setData(['foo' => 'bar']));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $this->assertEquals([
            'foo' => 'bar'
        ], $Endpoint->getData()->toArray());
        $this->assertEquals('bar', $Endpoint->getData()->foo);

    }

    /**
     * @covers ::setData
     * @throws MRussell\REST\Exception\Endpoint\InvalidDataType
     */
    public function testInvalidDataType()
    {
        $Endpoint = new SmartEndpointNoData();
        $this->expectException(\MRussell\REST\Exception\Endpoint\InvalidDataType::class);
        $this->expectExceptionMessage("Invalid data type passed to Endpoint [MRussell\REST\Tests\Stubs\Endpoint\SmartEndpointNoData]");
        $Endpoint->setData('test');
    }

    /**
     * @covers ::setData
     * @covers ::buildDataObject
     * @throws \MRussell\REST\Exception\Endpoint\InvalidDataType
     */
    public function testInvalidDataClass()
    {
        $Endpoint = new SmartEndpointNoData();
        $Reflected = new \ReflectionClass($Endpoint);
        $data = $Reflected->getProperty('data');
        $data->setAccessible(true);
        $DataClass = $Reflected->getProperty('_DATA_CLASS');
        $DataClass->setAccessible(true);
        $DataClass->setValue($Endpoint, "MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint");
        $data->setValue($Endpoint, null);
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("Missing or Invalid data on Endpoint Data. Errors: MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint does not implement MRussell\\REST\\Endpoint\\Data\\DataInterface");
        $Endpoint->setData([]);
    }

    /**
     * @covers ::reset
     * @covers ::buildDataObject
     * @return void
     */
    public function testReset()
    {
        $Endpoint = new SmartEndpoint();
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $Endpoint->getData()['foo'] = 'bar';
        $this->assertEquals('bar', $Endpoint->getData()['foo']);
        $Endpoint->reset();
        $this->assertEmpty($Endpoint->getData()->toArray());
        $this->assertTrue($Endpoint->getData()->isNull());
    }
}
