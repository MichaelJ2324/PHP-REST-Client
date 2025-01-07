<?php

namespace MRussell\REST\Tests\Endpoint\Data;

use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\Data\ValidatedEndpointData;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Tests\Stubs\Endpoint\DefaultedNonNullableData;
use MRussell\REST\Tests\Stubs\Endpoint\ValidatedData;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractEndpointDataTest
 * @package MRussell\REST\Tests\Endpoint\Data
 * @coversDefaultClass \MRussell\REST\Endpoint\Data\ValidatedEndpointData
 * @group ValidatedEndpointData
 */
class ValidatedDataTest extends TestCase
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

    protected array $properties = [
        'required' => ['foo' => 'string'],
        'auto_validate' => true,
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
     * @covers ::configureDefaultData
     * @covers ::set
     * @covers ::toArray
     * @covers ::getProperties
     */
    public function testConstructor(): void
    {
        $Data = new ValidatedEndpointData();
        $this->assertEquals(true, $Data->isNull());
        $this->assertEquals([
            ValidatedEndpointData::DATA_PROPERTY_DEFAULTS => [],
            ValidatedEndpointData::DATA_PROPERTY_NULLABLE => true,
            ValidatedEndpointData::DATA_PROPERTY_REQUIRED => [],
            ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE => false,
        ], $Data->getProperties());
        $this->assertEquals([], $Data->toArray());
        $Data = new ValidatedEndpointData([], $this->properties);
        $props = $Data->getProperties();
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_REQUIRED, $props);
        $this->assertEquals($this->properties[ValidatedEndpointData::DATA_PROPERTY_REQUIRED], $props[ValidatedEndpointData::DATA_PROPERTY_REQUIRED]);
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE, $props);
        $this->assertEquals($this->properties[ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE], $props[ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE]);
    }

    /**
     * @covers ::setProperties
     * @covers ::getProperties
     */
    public function testSetProperties(): void
    {
        $Data = new ValidatedEndpointData();
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_REQUIRED, $Data->getProperties());
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE, $Data->getProperties());
        $Data->setProperties([]);
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_REQUIRED, $Data->getProperties());
        $this->assertArrayHasKey(ValidatedEndpointData::DATA_PROPERTY_AUTO_VALIDATE, $Data->getProperties());
        $Data->setProperties($this->properties);
        $this->assertEquals(array_merge($this->properties, ['defaults' => [],'nullable' => true]), $Data->getProperties());
    }

    /**
     * @covers ::validate
     * @covers ::verifyRequiredData
     * @covers ::toArray
     */
    public function testToArray(): void
    {
        $Data = new ValidatedData();
        $Data['foo'] = 'bar';
        $this->assertEquals(['foo' => 'bar'], $Data->toArray(false));
        $Data['stuff'] = [];
        $this->assertEquals(['foo' => 'bar', 'stuff' => []], $Data->toArray(true));
    }

    public function testMissingData(): void
    {
        $Data = new ValidatedEndpointData();
        $Data->setProperties([
            ValidatedEndpointData::DATA_PROPERTY_REQUIRED => [
                'foo' => 'string',
            ],
        ]);
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("Missing or Invalid data on Endpoint Data. Errors: Missing [foo]");
        $Data->validate();
    }

    public function testInvalidData(): void
    {
        $Data = new ValidatedEndpointData();
        $Data->setProperties([
            ValidatedEndpointData::DATA_PROPERTY_REQUIRED => [
                'foo' => 'string',
            ],
        ]);
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("Missing or Invalid data on Endpoint Data. Errors: Invalid [foo]");
        $Data['foo'] = 1234;
        $Data->validate();
    }

    public function testInvalidAndMissingData(): void
    {
        $Data = new ValidatedEndpointData();
        $Data->setProperties([
            ValidatedEndpointData::DATA_PROPERTY_REQUIRED => [
                'foo' => 'string',
                'bar' => null,
            ],
        ]);
        $this->expectException(InvalidData::class);
        $this->expectExceptionMessage("Missing or Invalid data on Endpoint Data. Errors: Invalid [foo]");
        $Data['foo'] = 1234;
        $Data['bar'] = 'test';
        $Data->validate();
    }
}
