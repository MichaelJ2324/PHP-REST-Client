<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Exception\Endpoint\InvalidDataType;

abstract class AbstractSmartEndpoint extends AbstractEndpoint
{
    public const PROPERTY_DATA = 'data';

    /**
     * @inheritdoc
     */
    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_URL => '',
        self::PROPERTY_HTTP_METHOD => '',
        self::PROPERTY_AUTH => false,
        self::PROPERTY_DATA => [
            EndpointData::DATA_PROPERTY_DEFAULTS => [],
            EndpointData::DATA_PROPERTY_NULLABLE => [],
        ],
    ];

    protected string $_dataInterface = EndpointData::class;

    /**
     * The data being passed to the API Endpoint.
     * Uses the DataInterface to provide a more robust way of configuring data and an automation API
     */
    protected string|array|\ArrayAccess|null $_data;

    public function __construct(array $properties = [], array $urlArgs = [])
    {
        parent::__construct($properties, $urlArgs);
        $this->setData($this->buildDataObject());
    }

    /**
     * @inheritdoc
     * Passes through the data properties on the Data Object
     * @return $this
     */
    public function setProperties(array $properties): static
    {
        if (!isset($properties[self::PROPERTY_DATA])) {
            $properties[self::PROPERTY_DATA] = [];
        }

        parent::setProperties($properties);
        $this->configureDataProperties();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperty(string $name, $value): static
    {
        parent::setProperty($name, $value);
        if ($name === self::PROPERTY_DATA && isset($this->_data)) {
            $this->configureDataProperties();
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setData(string|array|\ArrayAccess|null $data): static
    {
        if ($data instanceof DataInterface) {
            $this->_data = $data;
        } elseif (is_array($data)) {
            $this->getData()->reset();
            $this->getData()->set($data);
        } elseif (is_null($data)) {
            $this->getData()->clear();
        } else {
            throw new InvalidDataType(static::class);
        }

        return $this;
    }

    /**
     * Get the current data object, or build out a new one if one is not set
     */
    public function getData(): DataInterface
    {
        if (!isset($this->_data)) {
            $this->_data = $this->buildDataObject();
        }

        $data = parent::getData();
        if (!($data instanceof DataInterface)) {
            $di = $this->buildDataObject();
            $this->_data = $di->set($data);
        }

        return $this->_data;
    }

    /**
     * Passes Data properties to Endpoint Data object
     * @return $this
     */
    protected function configureDataProperties(): static
    {
        $dataProps = $this->getProperty(self::PROPERTY_DATA);
        if (!empty($dataProps)) {
            $this->getData()->setProperties($dataProps);
        }

        return $this;
    }

    /**
     * Parse Data Object to array for handling by Guzzle
     * @param $data
     */
    protected function configureRequest(Request $request, $data): Request
    {
        $parsedData = $data;
        if ($data instanceof DataInterface) {
            $parsedData = $data->toArray();
            if (method_exists($data, 'isNull')) {
                $parsedData = $data->isNull() ? null : $parsedData;
            }
        }

        return parent::configureRequest($request, $parsedData);
    }

    /**
     * @inheritDoc
     * Reset data
     * @return $this
     */
    public function reset(): static
    {
        $this->getData()->reset();
        return parent::reset();
    }

    /**
     * Build out the configured Data Object for the Endpoint
     * @throws InvalidData
     */
    protected function buildDataObject(): DataInterface
    {
        $implements = class_implements($this->_dataInterface);
        if (is_array($implements) && isset($implements[DataInterface::class])) {
            return new $this->_dataInterface([], $this->getProperty(self::PROPERTY_DATA) ?? []);
        }

        throw new InvalidData($this->_dataInterface . " does not implement MRussell\\REST\\Endpoint\\Data\\DataInterface");
    }
}
