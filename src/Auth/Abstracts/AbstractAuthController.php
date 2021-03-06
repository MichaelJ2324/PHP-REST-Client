<?php

namespace MRussell\REST\Auth\Abstracts;

use MRussell\REST\Auth\AuthControllerInterface;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Storage\StorageControllerInterface;

abstract class AbstractAuthController implements AuthControllerInterface
{
    const ACTION_AUTH = 'authenticate';

    const ACTION_LOGOUT = 'logout';

    /**
     * Auth Controller Actions, used to associate Endpoints
     * @var array
     */
    protected static $_DEFAULT_AUTH_ACTIONS = array(
        self::ACTION_AUTH,
        self::ACTION_LOGOUT,
    );

    /**
     * Configured Actions on the Controlller
     * @var array
     */
    protected $actions = array();

    /**
     * Configured Endpoints for configured actions
     * @var array
     */
    protected $endpoints = array();

    /**
     * The credentials used for authentication
     * @var array
     */
    protected $credentials = array();

    /**
     * The authentication token
     * @var mixed
     */
    protected $token = null;

    /**
     * @var StorageControllerInterface
     */
    protected $Storage;

    public function __construct()
    {
        foreach (static::$_DEFAULT_AUTH_ACTIONS as $action) {
            $this->actions[] = $action;
        }
    }

    /**
     * @inheritdoc
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Set the Token on the Authentication Controller
     * @param $token
     * @return $this
     */
    protected function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Clear the token property to NULL
     */
    protected function clearToken()
    {
        $this->token = null;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @inheritdoc
     */
    public function setActionEndpoint($action, EndpointInterface $Endpoint)
    {
        if (in_array($action, $this->actions)) {
            $this->endpoints[$action] = $Endpoint;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getActionEndpoint($action)
    {
        if (isset($this->endpoints[$action])) {
            return $this->endpoints[$action];
        }
        return NULL;
    }

    /**
     * @inheritdoc
     */
    public function isAuthenticated()
    {
        if (!empty($this->token)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function authenticate()
    {
        $Endpoint = $this->getActionEndpoint(self::ACTION_AUTH);
        if ($Endpoint !== NULL) {
            $Endpoint = $this->configureEndpoint($Endpoint,self::ACTION_AUTH);
            $response = $Endpoint->execute()->getResponse();
            if ($response->getStatus() == '200') {
                //@codeCoverageIgnoreStart
                $this->setToken($response->getBody());
                return TRUE;
            }
            //@codeCoverageIgnoreEnd
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function logout()
    {
        $Endpoint = $this->getActionEndpoint(self::ACTION_LOGOUT);
        if ($Endpoint !== NULL){
            $Endpoint = $this->configureEndpoint($Endpoint,self::ACTION_LOGOUT);
            $response = $Endpoint->execute()->getResponse();
            if ($response->getStatus() == '200') {
                //@codeCoverageIgnoreStart
                $this->clearToken();
                return TRUE;
            }
            //@codeCoverageIgnoreEnd
        }
        return FALSE;
    }

    /**
     * @inheritDoc
     **/
    public function reset()
    {
        $this->credentials = array();
        return $this->clearToken();
    }

    /**
     * @inheritdoc
     */
    public function setStorageController(StorageControllerInterface $Storage)
    {
        $this->Storage = $Storage;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStorageController()
    {
        return $this->Storage;
    }

    /**
     * @inheritdoc
     */
    public function storeToken($key, $token)
    {
        if (isset($this->Storage)) {
            return $this->Storage->store($key, $token);
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function getStoredToken($key)
    {
        if (isset($this->Storage)) {
            return $this->Storage->get($key);
        }
        return NULL;
    }

    /**
     * @inheritdoc
     */
    public function removeStoredToken($key)
    {
        if (isset($this->Storage)){
            return $this->Storage->remove($key);
        }
        return FALSE;
    }

    /**
     * Configure an actions Endpoint Object
     * @param EndpointInterface $Endpoint
     * @param string $action
     * @return EndpointInterface
     */
    protected function configureEndpoint(EndpointInterface $Endpoint, $action)
    {
        switch ($action) {
            case self::ACTION_AUTH:
                $Endpoint = $this->configureAuthenticationEndpoint($Endpoint);
                break;
            case self::ACTION_LOGOUT:
                $Endpoint = $this->configureLogoutEndpoint($Endpoint);
                break;
        }
        return $Endpoint;
    }

    /**
     * Configure the data for the given Endpoint
     * @param EndpointInterface $Endpoint
     * @return EndpointInterface
     */
    protected function configureAuthenticationEndpoint(EndpointInterface $Endpoint)
    {
        return $Endpoint->setData($this->credentials);
    }

    /**
     *
     * @param EndpointInterface $Endpoint
     * @return EndpointInterface
     */
    protected function configureLogoutEndpoint(EndpointInterface $Endpoint)
    {
        return $Endpoint->setData(array());
    }

}