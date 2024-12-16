<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Data\AbstractEndpointData;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
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
     * @var string
     */
    protected static $_MODEL_ID_KEY = 'id';

    /**
     * The response property where the model data is located
     * @var string
     */
    protected static $_RESPONSE_PROP = '';

    /**
     * List of actions
     * @var array
     */
    protected static $_DEFAULT_ACTIONS = [self::MODEL_ACTION_CREATE => 'POST', self::MODEL_ACTION_RETRIEVE => 'GET', self::MODEL_ACTION_UPDATE => 'PUT', self::MODEL_ACTION_DELETE => 'DELETE'];

    /**
     * List of available actions and their associated Request Method
     * @var array
     */
    protected $actions = [];

    /**
     * Current action being executed
     * @var string
     */
    protected $action = self::MODEL_ACTION_RETRIEVE;

    //Static
    /**
     * @param null $id
     * @return string
     */
    public static function modelIdKey($id = null): string
    {
        if ($id !== null) {
            static::$_MODEL_ID_KEY = $id;
        }
        return static::$_MODEL_ID_KEY;
    }

    //Overloads
    public function __construct(array $urlArgs = [], array $properties = [])
    {
        parent::__construct($urlArgs, $properties);
        foreach (static::$_DEFAULT_ACTIONS as $action => $method) {
            $this->actions[$action] = $method;
        }
    }

    public function __call($name, $arguments)
    {
        if (array_key_exists($name, $this->actions)) {
            return $this->setCurrentAction($name, $arguments)->execute();
        }
        throw new UnknownModelAction([get_class($this), $name]);
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        parent::reset();
        return $this->clear();
    }

    /**
     * @inheritdoc
     * @throws \MRussell\REST\Exception\Endpoint\InvalidRequest
     */
    public function retrieve($id = null): ModelInterface
    {
        $this->setCurrentAction(self::MODEL_ACTION_RETRIEVE);
        $idKey = static::modelIdKey();
        if ($id !== null) {
            if (isset($this->_attributes[$idKey])) {
                $this->clear();
            }
            $this->set($idKey, $id);
        } elseif (!isset($this->_attributes[$idKey])) {
            throw new MissingModelId([$this->action, get_class($this)]);
        }
        $this->triggerEvent(self::EVENT_BEFORE_RETRIEVE);
        $this->execute();
        $this->triggerEvent(self::EVENT_AFTER_RETRIEVE);
        return $this;
    }

    /**
     * @inheritdoc
     * @throws \MRussell\REST\Exception\Endpoint\InvalidRequest
     */
    public function save(): ModelInterface
    {
        if (isset($this->_attributes[static::modelIdKey()])) {
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
    public function delete(): ModelInterface
    {
        $this->setCurrentAction(self::MODEL_ACTION_DELETE);
        $this->triggerEvent(self::EVENT_BEFORE_DELETE);
        $this->execute();
        $this->triggerEvent(self::EVENT_AFTER_DELETE);
        return $this;
    }

    /**
     * Set the current action taking place on the Model
     * @param string $action
     * @param array $actionArgs
     * @return $this
     */
    public function setCurrentAction(string $action, array $actionArgs = []): AbstractModelEndpoint
    {
        if (array_key_exists($action, $this->actions)) {
            $this->action = $action;
            $this->configureAction($this->action, $actionArgs);
        }
        return $this;
    }

    /**
     * Get the current action taking place on the Model
     */
    public function getCurrentAction(): string
    {
        return $this->action;
    }

    /**
     * Update any properties or data based on the current action
     * - Called when setting the Current Action
     * @param $action
     * @param array $arguments
     */
    protected function configureAction($action, array $arguments = [])
    {
        $this->setProperty(self::PROPERTY_HTTP_METHOD, $this->actions[$action]);
    }

    /**
     * @param AbstractEndpointData $data
     * @inheritdoc
     */
    protected function configurePayload()
    {
        $data = $this->getData() ?? null;
        switch ($this->getCurrentAction()) {
            case self::MODEL_ACTION_CREATE:
            case self::MODEL_ACTION_UPDATE:
                if (is_object($data)) {
                    $data->set($this->toArray());
                } elseif (is_array($data)) {
                    $data = array_replace($data, $this->toArray());
                }
                break;
        }
        $this->triggerEvent(self::EVENT_CONFIGURE_PAYLOAD, $data);
        return $data;
    }

    /**
     * @param Response $response
     * @return EndpointInterface
     */
    public function setResponse(Response $response): EndpointInterface
    {
        parent::setResponse($response);
        $this->parseResponse($response);
        return $this;
    }

    /**
     * @return string
     */
    public function getModelResponseProp(): string
    {
        return $this->getProperty(self::PROPERTY_RESPONSE_PROP) ?? static::$_RESPONSE_PROP;
    }

    /**
     * Parse the response for use by Model
     * @param Response $response
     * @return void
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
    protected function syncFromApi(array $model)
    {
        $this->triggerEvent(self::EVENT_BEFORE_SYNC, $model);
        $this->set($model);
    }

    /**
     * @param array $urlArgs
     * @return string
     */
    protected function configureURL(array $urlArgs): string
    {
        if (empty($urlArgs[self::MODEL_ID_VAR])) {
            switch ($this->getCurrentAction()) {
                case self::MODEL_ACTION_CREATE:
                    $urlArgs[self::MODEL_ID_VAR] = '';
                    break;
                default:
                    $idKey = static::modelIdKey();
                    $id = $this->get($idKey);
                    $urlArgs[self::MODEL_ID_VAR] = (empty($id) ? '' : $id);
            }
        }
        return parent::configureURL($urlArgs);
    }
}
