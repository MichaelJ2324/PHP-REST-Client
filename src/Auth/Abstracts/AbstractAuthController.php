<?php

namespace MRussell\REST\Auth\Abstracts;

use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\AuthControllerInterface;
use MRussell\REST\Cache\MemoryCache;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Auth\InvalidAuthenticationAction;
use MRussell\REST\Traits\PsrSimpleCacheTrait;
use MRussell\REST\Traits\PsrLoggerTrait;

abstract class AbstractAuthController implements AuthControllerInterface
{
    use PsrLoggerTrait;
    use PsrSimpleCacheTrait;

    public const ACTION_AUTH = 'authenticate';
    public const ACTION_LOGOUT = 'logout';

    /**
     * Auth Controller Actions, used to associate Endpoints
     */
    protected static array $_DEFAULT_AUTH_ACTIONS = [self::ACTION_AUTH, self::ACTION_LOGOUT];

    /**
     * Configured Actions on the Controlller
     * @var array
     */
    protected $actions = [];

    /**
     * Configured Endpoints for configured actions
     * @var array
     */
    protected $endpoints = [];

    /**
     * The credentials used for authentication
     * @var array
     */
    protected $credentials = [];

    /**
     * The authentication token
     * @var mixed
     */
    protected $token;

    /**
     * The Cache Key to store the token
     * @var string
     */
    protected $cacheKey;

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
        $this->cacheKey = '';
        $token = $this->getCachedToken();
        if (!empty($token)) {
            $this->setToken($token);
        }
        return $this;
    }

    public function getCacheKey(): string
    {
        if (empty($this->cacheKey)) {
            $this->cacheKey = "AUTH_TOKEN_" . sha1(json_encode($this->credentials));
        }
        return $this->cacheKey;
    }

    /**
     * @inheritdoc
     */
    public function updateCredentials(array $credentials): AuthControllerInterface
    {
        return $this->setCredentials(array_replace($this->getCredentials(), $credentials));
    }

    /**
     * @inheritdoc
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @inheritDoc
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->cacheToken();
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
     * Clear the token property to null
     * @return $this
     */
    public function clearToken()
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
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @inheritdoc
     */
    public function setActionEndpoint(string $action, EndpointInterface $Endpoint): AuthControllerInterface
    {
        if (in_array($action, $this->actions)) {
            $this->endpoints[$action] = $Endpoint;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getActionEndpoint($action): EndpointInterface
    {
        if (isset($this->endpoints[$action])) {
            return $this->endpoints[$action];
        }
        throw new InvalidAuthenticationAction([$action, self::class]);
    }

    /**
     * @inheritdoc
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->token);
    }

    /**
     * @inheritdoc
     */
    public function authenticate(): bool
    {
        try {
            $Endpoint = $this->configureEndpoint($this->getActionEndpoint(self::ACTION_AUTH), self::ACTION_AUTH);
            $response = $Endpoint->execute()->getResponse();
            $ret = $response->getStatusCode() == 200;
            if ($ret) {
                $token = $this->parseResponseToToken(self::ACTION_AUTH, $response);
                $this->setToken($token);
            }
        } catch (\Exception $e) {
            $this->getLogger()->error("[REST] Authenticate Exception - " . $e->getMessage());
            $ret = false;
        }
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function logout(): bool
    {
        $ret = false;
        try {
            $Endpoint = $this->configureEndpoint($this->getActionEndpoint(self::ACTION_LOGOUT), self::ACTION_LOGOUT);
            $response = $Endpoint->execute()->getResponse();
            $ret = $response->getStatusCode() == 200;
            if ($ret) {
                $this->clearToken();
                $this->removeCachedToken();
            }
        } catch (InvalidAuthenticationAction $ex) {
            $this->getLogger()->debug($ex->getMessage());
        } catch (\Exception $ex) {
            $this->getLogger()->error("[REST] Logout Exception - " . $ex->getMessage());
        }
        return $ret;
    }

    /**
     * @inheritDoc
     **/
    public function reset(): AuthControllerInterface
    {
        $this->credentials = [];
        return $this->clearToken();
    }

    /**
     * Cache the current token on the Auth Controller
     */
    protected function cacheToken(): bool
    {
        return $this->getCache()->set($this->getCacheKey(), $this->token);
    }

    /**
     * Get the cached token for the Auth Controller
     * @return mixed
     */
    protected function getCachedToken()
    {
        return $this->getCache()->get($this->getCacheKey(), null);
    }

    /**
     * Remove the cached token from the Auth Controller
     */
    protected function removeCachedToken(): bool
    {
        return $this->getCache()->delete($this->getCacheKey());
    }

    /**
     * Configure an actions Endpoint Object
     * @param string $action
     */
    protected function configureEndpoint(EndpointInterface $Endpoint, $action): EndpointInterface
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
     */
    protected function configureAuthenticationEndpoint(EndpointInterface $Endpoint): EndpointInterface
    {
        return $Endpoint->setData($this->credentials);
    }


    protected function configureLogoutEndpoint(EndpointInterface $Endpoint): EndpointInterface
    {
        return $Endpoint->setData([]);
    }

    /**
     * Given a response from Authentication endpoint, parse the response
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    protected function parseResponseToToken(string $action, Response $response)
    {
        return null;
    }
}
