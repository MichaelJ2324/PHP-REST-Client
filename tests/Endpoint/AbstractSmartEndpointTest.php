<?php

namespace MRussell\REST\Tests\Endpoint;

use MRussell\REST\Exception\Endpoint\InvalidDataType;
use MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\SmartEndpoint;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Tests\Stubs\Endpoint\SmartEndpointNoData;
use PHPUnit\Framework\TestCase;

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
                'foo' => 'string',
            ],
            'defaults' => [
                'bar' => 'foo',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     * @covers ::configureDataProperties
     */
    public function testConstructor(): void
    {
        $Endpoint = new SmartEndpointNoData();
        $this->assertNotEmpty($Endpoint->getData());
        $Endpoint = new SmartEndpointNoData(['url' => 'bar'], ['foo']);
        $this->assertNotEmpty($Endpoint->getData());
        $this->assertEquals($Endpoint->getEndPointUrl(), 'bar');
        $this->assertEquals($Endpoint->getUrlArgs(), ['foo']);
        $Endpoint = new SmartEndpoint(
            $this->properties,
            ['foo'],
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
    public function testSetProperties(): void
    {
        $Endpoint = new SmartEndpoint();
        $Endpoint->setProperties([]);
        $this->assertEquals(['url' => '', 'httpMethod' => '', 'auth' => 1, 'data' => []], $Endpoint->getProperties());

        $Endpoint->setProperties($this->properties);
        $this->assertEquals([
            'url' => '',
            'httpMethod' => '',
            'auth' => 1,
            'data' => [
                'required' => [
                    'foo' => 'string',
                ],
                'defaults' => [
                    'bar' => 'foo',
                ],
            ],
        ], $Endpoint->getProperties());
        $dataProps = $Endpoint->getData()->getProperties();
        $this->assertArrayHasKey('required', $dataProps);
        $this->assertEquals(
            [
                'foo' => 'string',
            ],
            $dataProps['required'],
        );
        $this->assertArrayHasKey('defaults', $dataProps);
        $this->assertEquals(
            [
                'bar' => 'foo',
            ],
            $dataProps['defaults'],
        );

        $this->assertEquals($Endpoint, $Endpoint->setProperty('data', [
            'required' => [
                'foo' => 'string',
            ],
            'defaults' => [
            ],
        ]));
        $dataProps = $Endpoint->getData()->getProperties();
        $this->assertArrayHasKey('required', $dataProps);
        $this->assertEquals(
            [
                'foo' => 'string',
            ],
            $dataProps['required'],
        );
        $this->assertArrayHasKey('defaults', $dataProps);
        $this->assertEquals(
            [],
            $dataProps['defaults'],
        );
    }

    /**
     * @covers ::setData
     * @covers ::getData
     * @covers ::configurePayload
     */
    public function testSetData(): void
    {
        $Endpoint = new SmartEndpointNoData();
        $this->assertEquals($Endpoint, $Endpoint->setData(null));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $Endpoint = new SmartEndpointNoData();
        $this->assertEquals($Endpoint, $Endpoint->setData([]));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $Data = new EndpointData();
        $this->assertEquals($Endpoint, $Endpoint->setData($Data));
        $this->assertEquals($Endpoint, $Endpoint->setData(['foo' => 'bar']));
        $this->assertInstanceOf(DataInterface::class, $Endpoint->getData());
        $this->assertEquals([
            'foo' => 'bar',
        ], $Endpoint->getData()->toArray());
        $this->assertEquals('bar', $Endpoint->getData()->foo);

    }

    /**
     * @covers ::setData
     * @throws InvalidDataType
     */
    public function testInvalidDataType(): void
    {
        $Endpoint = new SmartEndpointNoData();
        $this->expectException(InvalidDataType::class);
        $this->expectExceptionMessage("Invalid data type passed to Endpoint [MRussell\REST\Tests\Stubs\Endpoint\SmartEndpointNoData]");
        $Endpoint->setData('test');
    }

    /**
     * @covers ::setData
     * @covers ::buildDataObject
     * @throws InvalidDataType
     */
    public function testInvalidDataClass(): void
    {
        $Endpoint = new SmartEndpointNoData();
        $Reflected = new \ReflectionClass($Endpoint);
        $data = $Reflected->getProperty('data');
        $data->setAccessible(true);

        $DataClass = $Reflected->getProperty('_dataInterface');
        $DataClass->setAccessible(true);

        $oldValue = $DataClass->getValue($Endpoint);
        $DataClass->setValue($Endpoint, PingEndpoint::class);

        $data->setValue($Endpoint, null);
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("Missing or Invalid data on Endpoint Data. Errors: MRussell\REST\Tests\Stubs\Endpoint\PingEndpoint does not implement MRussell\\REST\\Endpoint\\Data\\DataInterface");
        $Endpoint->setData([]);
        $DataClass->setValue($Endpoint, $oldValue);
    }

    /**
     * @covers ::reset
     * @covers ::buildDataObject
     */
    public function testReset(): void
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
