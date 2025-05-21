<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidRequest;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Data\AbstractEndpointData;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Interfaces\ModelInterface;
use MRussell\REST\Endpoint\Traits\ArrayObjectAttributesTrait;
use MRussell\REST\Endpoint\Traits\ClearAttributesTrait;
use MRussell\REST\Endpoint\Traits\GetAttributesTrait;
use MRussell\REST\Endpoint\Traits\ParseResponseBodyToArrayTrait;
use MRussell\REST\Endpoint\Traits\PropertiesTrait;
use MRussell\REST\Endpoint\Traits\SetAttributesTrait;
use MRussell\REST\Exception\Endpoint\MissingModelId;
use MRussell\REST\Exception\Endpoint\UnknownModelAction;

/**
 * Class AbstractModelEndpoint
 * @package MRussell\REST\Endpoint\Abstracts
 */
abstract class AbstractModelEndpoint extends AbstractSmartEndpoint implements ModelInterface, DataInterface
{
    use ArrayObjectAttributesTrait;
    use GetAttributesTrait;
    use SetAttributesTrait;
    use PropertiesTrait;
    use ClearAttributesTrait;
    use ParseResponseBodyToArrayTrait;

    public const PROPERTY_RESPONSE_PROP = 'response_prop';

    public const PROPERTY_MODEL_KEY = 'id_key';

    public const DEFAULT_MODEL_KEY = 'id';

    public const MODEL_ID_VAR = 'id';

    public const MODEL_ACTION_CREATE = 'create';

    public const MODEL_ACTION_RETRIEVE = 'retrieve';

    public const MODEL_ACTION_UPDATE = 'update';

    public const MODEL_ACTION_DELETE = 'delete';

    public const EVENT_BEFORE_SAVE = 'before_save';

    public const EVENT_AFTER_SAVE = 'after_save';

    public const EVENT_BEFORE_DELETE = 'before_delete';

    public const EVENT_AFTER_DELETE = 'after_delete';

    public const EVENT_BEFORE_RETRIEVE = 'before_retrieve';

    public const EVENT_AFTER_RETRIEVE = 'after_retrieve';

    public const EVENT_BEFORE_SYNC = 'before_sync';

    /**
     * The ID Field used by the Model
     */
    protected static string $_DEFAULT_MODEL_KEY = self::DEFAULT_MODEL_KEY;

    /**
     * List of actions
     */
    protected static array $_DEFAULT_ACTIONS = [self::MODEL_ACTION_CREATE => 'POST', self::MODEL_ACTION_RETRIEVE => 'GET', self::MODEL_ACTION_UPDATE => 'PUT', self::MODEL_ACTION_DELETE => 'DELETE'];

    /**
     * List of available actions and their associated Request Method
     */
    protected array $_actions = [];

    /**
     * Current action being executed
     */
    protected string $_action = '';

    public static function defaultModelKey(string $key = null): string
    {
        if (!empty($key)) {
            static::$_DEFAULT_MODEL_KEY = $key;
        }

        return static::$_DEFAULT_MODEL_KEY;
    }

    public function getKeyProperty(): string
    {
        return $this->getProperty(self::PROPERTY_MODEL_KEY) ?? static::defaultModelKey();
    }

    public function getId(): string|int|null
    {
        return $this->get($this->getKeyProperty()) ?? null;
    }

    //Overloads
    public function __construct(array $properties = [], array $urlArgs = [])
    {
        parent::__construct($properties, $urlArgs);
        foreach (static::$_DEFAULT_ACTIONS as $action => $method) {
            $this->_actions[$action] = $method;
        }
    }

    public function __call($name, $arguments): EndpointInterface
    {
        if (array_key_exists($name, $this->_actions)) {
            return $this->setCurrentAction($name, $arguments)->execute();
        }

        throw new UnknownModelAction([static::class, $name]);
    }

    public function setUrlArgs(array $args): static
    {
        parent::setUrlArgs($args);
        $this->setIdFromUrlArgs();
        return $this;
    }

    protected function setIdFromUrlArgs(): void
    {
        if (!empty($this->_urlArgs[static::MODEL_ID_VAR])) {
            $id = $this->getId();
            if ($id != $this->_urlArgs[static::MODEL_ID_VAR] && !empty($id)) {
                $this->clear();
            }

            $prop = $this->getKeyProperty();
            $this->set($prop, $this->_urlArgs[static::MODEL_ID_VAR]);
            unset($this->_urlArgs[static::MODEL_ID_VAR]);
        }
    }

    /**
     * @inheritdoc
     */
    public function reset(): static
    {
        parent::reset();
        return $this->clear();
    }

    protected function setDefaultAction(): void
    {
        if (empty($this->_action)) {
            if (!empty($this->getId())) {
                $this->setCurrentAction(self::MODEL_ACTION_RETRIEVE);
            } else {
                $this->setCurrentAction(self::MODEL_ACTION_CREATE);
            }
        }
    }

    public function buildRequest(): Request
    {
        $this->setDefaultAction();
        return parent::buildRequest();
    }

    /**
     * @inheritdoc
     * @throws InvalidRequest
     */
    public function retrieve($id = null): static
    {
        $this->setCurrentAction(self::MODEL_ACTION_RETRIEVE);
        $idKey = $this->getKeyProperty();
        if ($id !== null) {
            if (isset($this->_attributes[$idKey])) {
                $this->clear();
            }

            $this->set($idKey, $id);
        } elseif (!isset($this->_attributes[$idKey])) {
            throw new MissingModelId([$this->_action, static::class]);
        }

        $this->triggerEvent(self::EVENT_BEFORE_RETRIEVE);
        $this->execute();
        $this->triggerEvent(self::EVENT_AFTER_RETRIEVE);
        return $this;
    }

    /**
     * @inheritdoc
     * @throws InvalidRequest
     */
    public function save(): static
    {
        if (!empty($this->getId())) {
            $this->setCurrentAction(self::MODEL_ACTION_UPDATE);
        } else {
            $this->setCurrentAction(self::MODEL_ACTION_CREATE);
        }

        $this->triggerEvent(self::EVENT_BEFORE_SAVE);
        $this->execute();
        $this->triggerEvent(self::EVENT_AFTER_SAVE);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function delete(): static
    {
        $this->setCurrentAction(self::MODEL_ACTION_DELETE);
        $this->triggerEvent(self::EVENT_BEFORE_DELETE);
        $this->execute();
        $this->triggerEvent(self::EVENT_AFTER_DELETE);
        return $this;
    }

    /**
     * Set the current action taking place on the Model
     */
    public function setCurrentAction(string $action, array $actionArgs = []): static
    {
        if (array_key_exists($action, $this->_actions)) {
            $this->_action = $action;
            $this->configureAction($this->_action, $actionArgs);
        }

        return $this;
    }

    /**
     * Get the current action taking place on the Model
     */
    public function getCurrentAction(): string
    {
        return $this->_action;
    }

    /**
     * Update any properties or data based on the current action
     * - Called when setting the Current Action
     * @param $action
     */
    protected function configureAction(string $action, array $arguments = []): void
    {
        $this->setProperty(self::PROPERTY_HTTP_METHOD, $this->_actions[$action]);
    }

    /**
     * @param AbstractEndpointData $data
     * @inheritdoc
     */
    protected function configurePayload(): mixed
    {
        $data = $this->getData();
        switch ($this->getCurrentAction()) {
            case self::MODEL_ACTION_CREATE:
            case self::MODEL_ACTION_UPDATE:
                $data->set($this->toArray());
                break;
        }

        $this->triggerEvent(self::EVENT_CONFIGURE_PAYLOAD, $data);
        return $data;
    }

    protected function setResponse(Response $response): static
    {
        parent::setResponse($response);
        $this->parseResponse($response);
        return $this;
    }

    public function getModelResponseProp(): string
    {
        return $this->getProperty(self::PROPERTY_RESPONSE_PROP) ?? "";
    }

    /**
     * Parse the response for use by Model
     */
    protected function parseResponse(Response $response): void
    {
        if ($response->getStatusCode() == 200) {
            switch ($this->getCurrentAction()) {
                case self::MODEL_ACTION_CREATE:
                case self::MODEL_ACTION_UPDATE:
                case self::MODEL_ACTION_RETRIEVE:
                    $body = $this->getResponseContent($response);
                    $this->syncFromApi($this->parseResponseBodyToArray($body, $this->getModelResponseProp()));
                    break;
                case self::MODEL_ACTION_DELETE:
                    $this->clear();
                    break;
            }
        }
    }

    /**
     * Called after Execute if a Request Object exists, and Request returned 200 response
     */
    protected function syncFromApi(array $model): void
    {
        $this->triggerEvent(self::EVENT_BEFORE_SYNC, $model);
        $this->set($model);
    }

    protected function configureURL(array $urlArgs): string
    {
        if (empty($urlArgs[static::MODEL_ID_VAR])) {
            switch ($this->getCurrentAction()) {
                case self::MODEL_ACTION_CREATE:
                    $urlArgs[self::MODEL_ID_VAR] = '';
                    break;
                default:
                    $id = $this->getId();
                    $urlArgs[self::MODEL_ID_VAR] = (empty($id) ? '' : $id);
            }
        }

        return parent::configureURL($urlArgs);
    }
}
