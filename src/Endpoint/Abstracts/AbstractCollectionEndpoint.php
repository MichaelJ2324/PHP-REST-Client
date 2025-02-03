<?php

namespace MRussell\REST\Endpoint\Abstracts;

use MRussell\REST\Endpoint\Traits\GenerateEndpointTrait;
use MRussell\REST\Exception\Endpoint\InvalidRequest;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Interfaces\CollectionInterface;
use MRussell\REST\Endpoint\Interfaces\ModelInterface;
use MRussell\REST\Endpoint\Traits\ParseResponseBodyToArrayTrait;
use MRussell\REST\Exception\Endpoint\UnknownEndpoint;

abstract class AbstractCollectionEndpoint extends AbstractSmartEndpoint implements
    CollectionInterface,
    \ArrayAccess,
    \Iterator
{
    use ParseResponseBodyToArrayTrait;
    use GenerateEndpointTrait;

    public const PROPERTY_RESPONSE_PROP = AbstractModelEndpoint::PROPERTY_RESPONSE_PROP;

    public const PROPERTY_MODEL_ENDPOINT = 'model';

    public const PROPERTY_MODEL_ID_KEY = AbstractModelEndpoint::PROPERTY_MODEL_KEY;

    public const EVENT_BEFORE_SYNC = 'before_sync';

    public const SETOPT_MERGE = 'merge';

    public const SETOPT_RESET = 'reset';

    protected string $_modelInterface = '';

    /**
     * The ID Field used by the Model
     */
    protected static string $_DEFAULT_MODEL_KEY = AbstractModelEndpoint::DEFAULT_MODEL_KEY;

    /**
     * The Collection of Models
     */
    protected array $models = [];

    /**
     * The Class Name of the ModelEndpoint
     */
    protected string $model;

    /**
     * Assigns a value to the specified offset
     * @param string $offset - The offset to assign the value to
     * @param mixed $value - The value to set
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->models[] = $value;
        } else {
            $this->models[$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists
     * @param string $offset - An offset to check for
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->models[$offset]);
    }

    /**
     * Unsets an offset
     * @param string $offset - The offset to unset
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset): void
    {
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
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->models[$offset] : null;
    }

    /**
     * @implements ArrayableInterface
     */
    public function toArray(): array
    {
        return $this->models;
    }

    /**
     * @return $this
     * @implements ResettableInterface
     */
    public function reset(): static
    {
        parent::reset();
        return $this->clear();
    }

    /**
     *
     * @return $this
     * @implements ClearableInterface
     */
    public function clear(): static
    {
        $this->models = [];
        return $this;
    }

    //Iterator
    /**
     * @return mixed|void
     * @implements \Iterator
     */

    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->models);
    }

    /**
     * @return mixed|void
     * @implements \Iterator
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->models);
    }

    /**
     * @implements \Iterator
     */
    public function next(): void
    {
        next($this->models);
    }

    /**
     * @implements \Iterator
     */
    public function rewind(): void
    {
        reset($this->models);
    }

    /**
     * @return mixed|void
     * @implements \Iterator
     */
    public function valid(): bool
    {
        return key($this->models) !== null;
    }

    //Collection Interface
    /**
     * @inheritdoc
     * @throws InvalidRequest
     */
    public function fetch(): static
    {
        $this->setProperty(self::PROPERTY_HTTP_METHOD, "GET");
        return $this->execute();
    }

    /**
     * @inheritdoc
     */
    public function get(string|int $key): ModelInterface|array|\ArrayAccess|null
    {
        $data = null;
        if ($this->offsetExists($key)) {
            $data = $this->models[$key];
            $Model = $this->buildModel($data);
            if ($Model instanceof ModelInterface) {
                $data = $Model;
            }
        }

        return $data;
    }

    /**
     * Get a model based on numerical index
     */
    public function at(int $index): ModelInterface|array|\ArrayAccess|null
    {
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
        if ($Model instanceof ModelInterface) {
            $return = $Model;
        }

        return $return;
    }

    protected function getModelIdKey(): string
    {
        $model = $this->buildModel();
        if ($model instanceof ModelInterface) {
            return $model->getKeyProperty();
        }

        return $this->getProperty(self::PROPERTY_MODEL_ID_KEY) ?? static::$_DEFAULT_MODEL_KEY;
    }

    /**
     * Append models to the collection
     */
    public function set(array $models, array $options = []): static
    {
        $modelIdKey = $this->getModelIdKey();
        $reset = $options[self::SETOPT_RESET] ?? false;
        $merge = $options[self::SETOPT_MERGE] ?? false;
        if ($reset) {
            $this->models = [];
        }

        foreach ($models as $m) {
            if ($m instanceof DataInterface) {
                $m = $m->toArray();
            } elseif ($m instanceof \stdClass) {
                $m = (array) $m;
            }

            if (!empty($m[$modelIdKey])) {
                $id = $m[$modelIdKey];
                $this->models[$id] = $merge && isset($this->models[$id]) ? array_merge($this->models[$id], $m) : $m;
            } else {
                $this->models[] = $m;
            }
        }

        return $this;
    }

    /**
     * Return the current collection count
     */
    public function length(): int
    {
        return count($this->models);
    }

    /**
     * @inheritdoc
     * @throws UnknownEndpoint
     */
    public function setModelEndpoint(string|ModelInterface $model): static
    {
        try {
            $interface = $model;
            if (is_string($model)) {
                if (!class_exists($model) && (!empty($this->_client) && $this->_client->hasEndpoint($model))) {
                    $model = $this->_client->getEndpoint($model);
                    $interface = $model::class;
                }
            } else {
                $interface = $model::class;
            }

            $implements = class_implements($model);
            if (is_array($implements) && isset($implements[ModelInterface::class])) {
                $this->setProperty(self::PROPERTY_MODEL_ENDPOINT, $interface);
                return $this;
            }
        } catch (\Exception) {
            //If class_implements cannot load class
        }

        throw new UnknownEndpoint($model);
    }

    public function getEndPointUrl(bool $full = false): string
    {
        $epURL = parent::getEndPointUrl();
        if ($epURL === '') {
            $model = $this->buildModel();
            if ($model instanceof ModelInterface) {
                $epURL = $model->getEndPointUrl();
            }
        }

        if ($full) {
            $epURL = rtrim($this->getBaseUrl(), "/") . ('/' . $epURL);
        }

        return $epURL;
    }

    protected function setResponse(Response $response): static
    {
        parent::setResponse($response);
        $this->parseResponse($response);
        return $this;
    }

    public function getCollectionResponseProp(): string
    {
        return $this->getProperty(self::PROPERTY_RESPONSE_PROP) ?? '';
    }

    /**
     * @inheritdoc
     */
    protected function parseResponse(Response $response): void
    {
        if ($response->getStatusCode() == 200) {
            $body = $this->getResponseContent($response);
            $this->syncFromApi($this->parseResponseBodyToArray($body, $this->getCollectionResponseProp()));
        }
    }

    /**
     * Configures the collection based on the Response Body
     */
    protected function syncFromApi(array $data): void
    {
        $this->triggerEvent(self::EVENT_BEFORE_SYNC, $data);
        $this->set($data);
    }

    /**
     * Build the ModelEndpoint
     */
    protected function buildModel(array $data = []): ModelInterface|null
    {
        $Model = null;
        $endpoint = $this->getProperty(self::PROPERTY_MODEL_ENDPOINT) ?? $this->_modelInterface;
        if (!empty($endpoint)) {
            $Model = $this->generateEndpoint($endpoint);

            if (!empty($data) && $Model instanceof ModelInterface) {
                $Model->set($data);
            }
        }

        return $Model;
    }
}
