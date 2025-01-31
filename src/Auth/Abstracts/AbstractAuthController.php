<?php

namespace MRussell\REST\Auth\Abstracts;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\AuthControllerInterface;
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
     * Configured Endpoints for configured actions
     */
    private array $endpoints = [];

    /**
     * Auth Controller Actions, used to associate Endpoints
     */
    protected static array $_DEFAULT_AUTH_ACTIONS = [self::ACTION_AUTH, self::ACTION_LOGOUT];

    /**
     * Configured Actions on the Controlller
     */
    protected array $actions = [];

    /**
     * The credentials used for authentication
     */
    protected array $credentials = [];

    /**
     * The authentication token
     */
    protected mixed $token;

    /**
     * The Cache Key to store the token
     */
    protected string $cacheKey;

    public function __construct()
    {
        foreach (static::$_DEFAULT_AUTH_ACTIONS as $action) {
            $this->actions[] = $action;
        }
    }

    /**
     * @inheritdoc
     */
    public function setCredentials(array $credentials): static
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
    public function updateCredentials(array $credentials): static
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
    public function setToken(mixed $token): static
    {
        $this->token = $token;
        $this->cacheToken();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getToken(): mixed
    {
        return $this->token ?? null;
    }

    /**
     * Clear the token property to null
     * @return $this
     */
    public function clearToken(): static
    {
        unset($this->token);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setActions(array $actions): static
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
    public function setActionEndpoint(string $action, EndpointInterface $Endpoint): static
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
        $ret = false;
        try {
            $Endpoint = $this->configureEndpoint($this->getActionEndpoint(self::ACTION_AUTH), self::ACTION_AUTH);
            $response = $Endpoint->execute()->getResponse();
            $ret = $response->getStatusCode() == 200;
            if ($ret) {
                $token = $this->parseResponseToToken(self::ACTION_AUTH, $response);
                $this->setToken($token);
            }
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $message = $exception->getMessage();
            $content = $response->getBody()->getContents();
            if (!empty($content)) {
                $message .= "RESPONSE: $content";
            }
            $this->getLogger()->error("[REST] Authenticate Failed [$statusCode] - " . $message);
            // @codeCoverageIgnoreStart
        } catch (\Exception $exception) {
            $this->getLogger()->error("[REST] Authenticate Exception - " . $exception->getMessage());
        }
        // @codeCoverageIgnoreEnd
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
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();
            $message = $exception->getMessage();
            $content = $response->getBody()->getContents();
            if (!empty($content)) {
                $message .= "RESPONSE: $content";
            }
            $this->getLogger()->error("[REST] Logout Failed [$statusCode] - " . $message);
            // @codeCoverageIgnoreStart
        } catch (\Exception $ex) {
            $this->getLogger()->error("[REST] Logout Exception - " . $ex->getMessage());
        }
        // @codeCoverageIgnoreEnd
        return $ret;
    }

    /**
     * @inheritDoc
     **/
    public function reset(): static
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
     */
    protected function getCachedToken(): mixed
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
        return match ($action) {
            self::ACTION_AUTH => $this->configureAuthenticationEndpoint($Endpoint),
            self::ACTION_LOGOUT => $this->configureLogoutEndpoint($Endpoint),
            default => $Endpoint,
        };
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
     * @codeCoverageIgnore
     */
    protected function parseResponseToToken(string $action, Response $response): mixed
    {
        return null;
    }
}
