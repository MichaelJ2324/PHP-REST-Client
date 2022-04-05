<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Interfaces\ArrayableInterface;
use MRussell\REST\Endpoint\Interfaces\ClearableInterface;
use MRussell\REST\Endpoint\Interfaces\CollectionInterface;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Endpoint\Interfaces\GetInterface;
use MRussell\REST\Endpoint\Interfaces\ModelInterface;
use MRussell\REST\Endpoint\Interfaces\PropertiesInterface;
use MRussell\REST\Endpoint\Interfaces\ResettableInterface;
use MRussell\REST\Endpoint\Interfaces\SetInterface;
use MRussell\REST\Endpoint\Traits\ParseResponseBodyToArrayTrait;
use MRussell\REST\Exception\Endpoint\UnknownEndpoint;

abstract class AbstractCollectionEndpoint extends AbstractSmartEndpoint implements CollectionInterface,
    \ArrayAccess,\Iterator {
    use ParseResponseBodyToArrayTrait;

    const PROPERTY_RESPONSE_PROP = 'response_prop';

    const EVENT_BEFORE_SYNC = 'before_sync';

    /**
     * @var string
     */
    protected static $_MODEL_CLASS = '';

    /**
     * @var string
     */
    protected static $_RESPONSE_PROP = '';

    /**
     * The Collection of Models
     * @var array
     */
    protected $models = array();

    /**
     * The Class Name of the ModelEndpoint
     * @var string
     */
    protected $model;

    public function __construct(array $urlArgs = array(), array $properties = array()) {
        parent::__construct($urlArgs, $properties);
        if (static::$_MODEL_CLASS !== '') {
            $this->setModelEndpoint(static::$_MODEL_CLASS);
        }
        $this->setProperty(self::PROPERTY_RESPONSE_PROP,static::$_RESPONSE_PROP);
    }

    /**
     * Assigns a value to the specified offset
     * @param string $offset - The offset to assign the value to
     * @param mixed $value - The value to set
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, $value): void {
        if (is_null($offset)) {
            $this->models[] = $value;
        } else {
            $this->models[$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists
     * @param string $offset - An offset to check for
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset): bool {
        return isset($this->models[$offset]);
    }

    /**
     * Unsets an offset
     * @param string $offset - The offset to unset
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset): void {
        if ($this->offsetExists($offset)) {
            unset($this->models[$offset]);
        }
    }

    /**
     * Returns the value at specified offset
     * @param string $offset - The offset to retrieve
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->models[$offset] : null;
    }

    /**
     * @return array
     * @implements ArrayableInterface
     */
    public function toArray(): array {
        return $this->models;
    }

    /**
     * @return $this
     * @implements ResettableInterface
     */
    public function reset() {
        parent::reset();
        return $this->clear();
    }

    /**
     *
     * @return $this
     * @implements ClearableInterface
     */
    public function clear() {
        $this->models = array();
        return $this;
    }

    //Iterator
    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function current() {
        return current($this->models);
    }

    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function key() {
        return key($this->models);
    }
    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function next(): void {
        next($this->models);
    }

    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function rewind(): void {
        reset($this->models);
    }

    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function valid(): bool {
        return key($this->models) !== null;
    }

    //Collection Interface
    /**
     * @inheritdoc
     * @throws \MRussell\REST\Exception\Endpoint\InvalidRequest
     */
    public function fetch() {
        $this->setProperty(self::PROPERTY_HTTP_METHOD, "GET");
        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function get($id) {
        $data = null;
        if ($this->offsetExists($id)) {
            $data = $this->models[$id];
            $Model = $this->buildModel($data);
            if ($Model !== null) {
                $data = $Model;
            }
        }
        return $data;
    }

    /**
     * Get a model based on numerical index
     * @param int $index
     * @return array|ModelInterface
     */
    public function at($index) {
        $return = null;
        $index = intval($index);
        $this->rewind();
        if ($index < 0) {
            $index += $this->length();
        }
        $c = 1;
        while ($c <= $index) {
            $this->next();
            $c++;
        }
        $return = $this->current();
        $Model = $this->buildModel($return);
        if ($Model !== null) {
            $return = $Model;
        }
        return $return;
    }

    /**
     * Append models to the collection
     * @param array $models
     * @return AbstractCollectionEndpoint
     */
    public function set(array $models)
    {
        if (isset($this->model)) {
            $modelIdKey = $this->buildModel()->modelIdKey();
            foreach ($models as $key => $model) {
                if ($model instanceof DataInterface){
                    $model = $model->toArray();
                }elseif ($model instanceof \stdClass){
                    $model = (array)$model;
                }
                if (isset($model[$modelIdKey])) {
                    $this->models[$model[$modelIdKey]] = $model;
                } else {
                    $this->models[] = $model;
                }
            }
        } else {
            $this->models = $models;
        }
        return $this;
    }

    /**
     * Return the current collection count
     * @return int
     */
    public function length(): int {
        return count($this->models);
    }

    /**
     * @inheritdoc
     * @throws UnknownEndpoint
     */
    public function setModelEndpoint($model): CollectionInterface {
        try {
            $implements = class_implements($model);
            if (is_array($implements) && isset($implements['MRussell\REST\Endpoint\Interfaces\ModelInterface'])) {
                if (is_object($model)) {
                    $model = get_class($model);
                }
                $this->model = $model;
                return $this;
            }
        } catch (\Exception $ex) {
            //If class_implements cannot load class
        }
        throw new UnknownEndpoint($model);
    }

    /**
     * @param bool $full
     * @return string
     */
    public function getEndPointUrl($full = false): string {
        $epURL = parent::getEndPointUrl();
        if ($epURL == '' && isset($this->model)) {
            $epURL = $this->buildModel()->getEndPointUrl();
        }
        if ($full) {
            $epURL = rtrim($this->getBaseUrl(), "/") . "/$epURL";
        }
        return $epURL;
    }

    /**
     * @param Response $response
     * @return EndpointInterface
     */
    public function setResponse(Response $response): EndpointInterface {
        parent::setResponse($response);
        $this->parseResponse($response);
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionResponseProp(): string
    {
        if (isset($this->properties[self::PROPERTY_RESPONSE_PROP])){
            $prop = $this->properties[self::PROPERTY_RESPONSE_PROP];
        }
        return $prop ?? static::$_RESPONSE_PROP;
    }

    /**
     * @inheritdoc
     */
    protected function parseResponse(Response $response): void {
        if ($response->getStatusCode() == 200) {
            $body = $this->getResponseBody();
            $this->syncFromApi($this->parseResponseBodyToArray($body,$this->getCollectionResponseProp()));
        }
    }

    /**
     * Configures the collection based on the Response Body
     */
    protected function syncFromApi(array $data) {
        $this->triggerEvent(self::EVENT_BEFORE_SYNC, $data);
        $this->set($data);
    }

    /**
     * Build the ModelEndpoint
     * @param array $data
     * @return AbstractModelEndpoint
     */
    protected function buildModel(array $data = array()): AbstractModelEndpoint {
        $Model = null;
        if (isset($this->model)) {
            $Model = new $this->model();
            if ($this->client){
                $Model->setClient($this->getClient());
            } else {
                $Model->setBaseUrl($this->getBaseUrl());
            }
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $Model->set($key, $value);
                }
            }
        }
        return $Model;
    }
}
