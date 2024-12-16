<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Data\AbstractEndpointData;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Exception\Endpoint\InvalidDataType;

abstract class AbstractSmartEndpoint extends AbstractEndpoint
{
    public const PROPERTY_DATA = 'data';

    /**
     * @inheritdoc
     */
    protected static $_DEFAULT_PROPERTIES = [self::PROPERTY_URL => '', self::PROPERTY_HTTP_METHOD => '', self::PROPERTY_AUTH => false, self::PROPERTY_DATA => ['required' => [], 'defaults' => []]];

    protected static $_DATA_CLASS = EndpointData::class;

    /**
     * The data being passed to the API Endpoint.
     * Uses the DataInterface to provide a more robust way of configuring data and an automation API
     * @var DataInterface
     */
    protected $data;

    public function __construct(array $urlArgs = [], array $properties = [])
    {
        parent::__construct($urlArgs, $properties);
        $this->setData($this->buildDataObject());
    }

    /**
     * @inheritdoc
     * Passes through the data properties on the Data Object
     * @return $this
     */
    public function setProperties(array $properties)
    {
        if (!isset($properties[self::PROPERTY_DATA])) {
            $properties[self::PROPERTY_DATA] = [
                'required' => [],
                'defaults' => [],
            ];
        }
        parent::setProperties($properties);
        $this->configureDataProperties();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperty(string $name, $value)
    {
        parent::setProperty($name, $value);
        if ($name == self::PROPERTY_DATA && isset($this->data)) {
            $this->configureDataProperties();
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setData($data): EndpointInterface
    {
        if ($data instanceof AbstractEndpointData) {
            $this->data = $data;
        } elseif (is_array($data)) {
            $this->getData()->reset();
            $this->getData()->set($data);
        } elseif (is_null($data)) {
            $this->data = $this->buildDataObject();
        } else {
            throw new InvalidDataType(get_class($this));
        }
        return $this;
    }

    /**
     * Get the current data object, or build out a new one if one is not set
     * @return DataInterface
     */
    public function getData()
    {
        if (!$this->data) {
            $this->data = $this->buildDataObject();
        }
        return parent::getData();
    }

    /**
     * Passes Data properties to Endpoint Data object
     * @return $this
     */
    protected function configureDataProperties(): EndpointInterface
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
    public function reset()
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
        $implements = class_implements(static::$_DATA_CLASS);
        if (is_array($implements) && isset($implements[\MRussell\REST\Endpoint\Data\DataInterface::class])) {
            return new static::$_DATA_CLASS([], $this->getProperty(self::PROPERTY_DATA) ?? []);
        }
        throw new InvalidData(static::$_DATA_CLASS . " does not implement MRussell\\REST\\Endpoint\\Data\\DataInterface");
    }
}
