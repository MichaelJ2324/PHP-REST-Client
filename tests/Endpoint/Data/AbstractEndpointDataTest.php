<?php

namespace MRussell\REST\Tests\Endpoint\Data;

use MRussell\REST\Endpoint\Data\EndpointData as StockData;
use MRussell\REST\Tests\Stubs\Endpoint\DefaultedNonNullableData;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractEndpointDataTest
 * @package MRussell\REST\Tests\Endpoint\Data
 * @coversDefaultClass \MRussell\REST\Endpoint\Data\AbstractEndpointData
 * @group AbstractEndpointDataTest
 */
class AbstractEndpointDataTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        //Add Setup for static properties here
    }

    public static function tearDownAfterClass(): void
    {
        //Add Tear Down for static properties here
    }

    protected array $data = ['foo' => 'bar', 'baz' => 'foz'];

    protected array $properties = ['defaults' => ['bar' => 'foo'], 'nullable' => false];

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
     * @covers ::configureDefaultData
     * @covers ::set
     * @covers ::toArray
     * @covers ::getProperties
     */
    public function testConstructor(): void
    {
        $Data = new StockData();
        $this->assertEquals(true, $Data->isNull());
        $this->assertEquals([StockData::DATA_PROPERTY_DEFAULTS => [], StockData::DATA_PROPERTY_NULLABLE => true], $Data->getProperties());
        $this->assertEquals([], $Data->toArray());
        $Data = new StockData([], $this->properties);
        $this->assertEquals($this->properties, $Data->getProperties());
        $this->assertEquals(['bar' => 'foo'], $Data->toArray());
        $this->assertEquals(false, $Data->isNull());
        $Data = new StockData($this->data, $this->properties);
        $this->assertEquals($this->properties, $Data->getProperties());
        $data = $this->data;
        $data['bar'] = 'foo';
        $this->assertEquals($data, $Data->toArray());
        $Data = new StockData($this->data, []);
        $this->assertEquals([StockData::DATA_PROPERTY_DEFAULTS => [], StockData::DATA_PROPERTY_NULLABLE => true], $Data->getProperties());
        $this->assertEquals($this->data, $Data->toArray());

        $Data = new DefaultedNonNullableData([], []);
        $this->assertEquals($this->properties, $Data->getProperties());
        $this->assertEquals(['bar' => 'foo'], $Data->toArray());
    }

    /**
     * @covers ::__get
     * @covers ::__set
     * @covers ::__isset
     * @covers ::__unset
     * @covers ::offsetSet
     * @covers ::offsetExists
     * @covers ::offsetUnset
     * @covers ::offsetGet
     * @covers ::toArray
     */
    public function testDataAccess(): void
    {
        $this->data = array_replace($this->data, ['test' => 'tester', 'abcd' => 'efg', 'pew' => 'die', 'arr' => [], 'iint' => 1234]);
        $Data = new StockData($this->data);
        $Data['bar'] = 'foo';
        $this->assertEquals('foo', $Data['bar']);
        $this->assertEquals('foo', $Data->bar);
        $Data->foz = 'baz';
        $this->assertEquals('baz', $Data['foz']);
        $this->assertEquals('baz', $Data->foz);
        $Data[] = 'number1';
        $this->assertEquals('number1', $Data[0]);
        $this->assertEquals('tester', $Data->test);
        $this->assertEquals(true, isset($Data->abcd));
        $this->assertEquals('die', $Data['pew']);
        $this->assertEquals([], $Data['arr']);
        $this->assertEquals([], $Data->arr);
        $this->assertEquals(true, isset($Data['iint']));
        unset($Data->arr);
        $this->assertEquals(false, isset($Data->arr));
        unset($Data['abcd']);
        $this->assertEquals(false, isset($Data['abcd']));
        $this->assertEquals([0 => 'number1', 'foo' => 'bar', 'bar' => 'foo', 'baz' => 'foz', 'foz' => 'baz', 'test' => 'tester', 'pew' => 'die', 'iint' => 1234], $Data->toArray());
    }

    /**
     * @covers ::setProperties
     * @covers ::getProperties
     */
    public function testSetProperties(): void
    {
        $Data = new StockData();
        $this->assertEquals([StockData::DATA_PROPERTY_DEFAULTS => [], StockData::DATA_PROPERTY_NULLABLE => true], $Data->getProperties());
        $Data->setProperties([]);
        $this->assertEquals([
            'defaults' => [],
            'nullable' => true,
        ], $Data->getProperties());
        $Data->setProperties($this->properties);
        $this->assertEquals($this->properties, $Data->getProperties());
    }

    /**
     * @depends testDataAccess
     * @covers ::reset
     * @covers ::clear
     */
    public function testReset(): void
    {
        $Data = new StockData();
        $Data['foo'] = 'bar';
        $Data->setProperties($this->properties);
        $this->assertEquals($Data, $Data->reset());
        $this->assertEquals([StockData::DATA_PROPERTY_DEFAULTS => [], StockData::DATA_PROPERTY_NULLABLE => true], $Data->getProperties());
        $this->assertEquals([], $Data->toArray());
        $this->assertEquals(true, $Data->isNull());

        $Data = new DefaultedNonNullableData($this->data, $this->properties);
        $this->assertEquals($Data, $Data->clear());
        $this->assertEquals(false, $Data->isNull());
        $this->assertEquals([], $Data->toArray());
        $this->assertEquals($this->properties, $Data->getProperties());
    }

    /**
     * @covers ::isNull
     * @covers ::clear
     */
    public function testNullable(): void
    {
        $Data = new StockData();
        $this->assertEquals(true, $Data->isNull());
        $Data['foobar'] = 'test';
        $this->assertEquals(false, $Data->isNull());
        $this->assertEquals($Data, $Data->clear());
        $this->assertEquals(true, $Data->isNull());
    }
}
