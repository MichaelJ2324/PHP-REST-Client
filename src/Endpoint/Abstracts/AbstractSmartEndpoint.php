<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Data\AbstractEndpointData;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Data\EndpointData;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidData;
use MRussell\REST\Exception\Endpoint\InvalidDataType;

abstract class AbstractSmartEndpoint extends AbstractEndpoint {
    const PROPERTY_DATA = 'data';

    /**
     * @inheritdoc
     */
    protected static $_DEFAULT_PROPERTIES = array(
        self::PROPERTY_URL => '',
        self::PROPERTY_HTTP_METHOD => '',
        self::PROPERTY_AUTH => false,
        self::PROPERTY_DATA => array(
            'required' => array(),
            'defaults' => array()
        )
    );

    protected static $_DATA_CLASS = EndpointData::class;

    /**
     * The data being passed to the API Endpoint.
     * Defaults to Array, but can be mixed based on how you want to use Endpoint.
     * @var AbstractEndpointData
     */
    protected $data;

    public function __construct(array $options = array(), array $properties = array()) {
        parent::__construct($options, $properties);
        $this->setData($this->buildDataObject());
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $properties) {
        if (!isset($properties[self::PROPERTY_DATA])) {
            $properties[self::PROPERTY_DATA] = [
                'required' => [],
                'defaults' => []
            ];
        }
        parent::setProperties($properties);
        $this->configureDataProperties();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperty(string $name, $value) {
        parent::setProperty($name, $value);
        if ($name == self::PROPERTY_DATA && isset($this->data)) {
            $this->configureDataProperties();
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setData($data): EndpointInterface {
        if ($data instanceof AbstractEndpointData) {
            $this->data = $data;
        } else if (is_array($data)) {
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
     * @return DataInterface
     */
    public function getData()
    {
        if (!$this->data){
            $this->data = $this->buildDataObject();
        }
        return parent::getData();
    }

    /**
     * Passes Data properties to Endpoint Data object
     * @return $this
     */
    protected function configureDataProperties(): EndpointInterface {
        if (isset($this->properties[self::PROPERTY_DATA]) && $this->data) {
            $this->data->setProperties($this->properties['data']);
        }
        return $this;
    }

    /**
     * @param Request $request
     * @param $data
     * @return Request
     */
    protected function configureRequest(Request $request, $data): Request {
        $parsedData = $data;
        if ($data instanceof DataInterface) {
            $parsedData = $data->toArray();
            if (method_exists($data,'isNull')){
                $parsedData = $data->isNull()?null:$parsedData;
            }
        }
        return parent::configureRequest($request, $parsedData);
    }

    /**
     * @return mixed
     * @throws InvalidData
     */
    protected function buildDataObject(): DataInterface {
        $implements = class_implements(static::$_DATA_CLASS);
        if (is_array($implements) && isset($implements["MRussell\\REST\\Endpoint\\Data\\DataInterface"])) {
            return new static::$_DATA_CLASS([], $this->properties[self::PROPERTY_DATA] ?? []);
        }
        throw new InvalidData(static::$_DATA_CLASS." does not implement MRussell\\REST\\Endpoint\\Data\\DataInterface");
    }
}
