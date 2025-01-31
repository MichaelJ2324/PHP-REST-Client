<?php

namespace MRussell\REST\Auth\Abstracts;

use GuzzleHttp\Exception\RequestException;
use Psr\SimpleCache\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\AuthControllerInterface;
use GuzzleHttp\Psr7\Request;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Auth\InvalidAuthenticationAction;
use MRussell\REST\Exception\Auth\InvalidToken;

abstract class AbstractOAuth2Controller extends AbstractBasicController
{
    public const DEFAULT_AUTH_TYPE = 'Bearer';

    public const ACTION_OAUTH_REFRESH = 'refresh';

    public const OAUTH_RESOURCE_OWNER_GRANT = 'password';

    public const OAUTH_CLIENT_CREDENTIALS_GRANT = 'client_credentials';

    public const OAUTH_AUTHORIZATION_CODE_GRANT = 'authorization_code';

    public const OAUTH_REFRESH_GRANT = 'refresh_token';

    public const OAUTH_TOKEN_EXPIRES_IN = 'expires_in';

    public const OAUTH_TOKEN_ACCESS_TOKEN = 'access_token';

    public const OAUTH_TOKEN_REFRESH_TOKEN = 'refresh_token';

    public const OAUTH_TOKEN_EXPIRATION = 'expiration';

    protected static string $_DEFAULT_GRANT_TYPE = self::OAUTH_CLIENT_CREDENTIALS_GRANT;

    /**
     * The type of OAuth token we are receiving
     */
    protected string $authType = self::DEFAULT_AUTH_TYPE;

    /**
     * @inheritdoc
     */
    protected static array $_DEFAULT_AUTH_ACTIONS = [self::ACTION_AUTH, self::ACTION_LOGOUT, self::ACTION_OAUTH_REFRESH];

    protected string $grant_type;

    public function __construct()
    {
        parent::__construct();
        $this->setGrantType(static::$_DEFAULT_GRANT_TYPE);
    }

    /**
     * @param $grant_type
     * @return $this
     */
    public function setGrantType(string $grant_type): AuthControllerInterface
    {
        $this->grant_type = $grant_type;
        return $this;
    }

    public function getGrantType(): string
    {
        return $this->grant_type;
    }

    /**
     * Get/Set the OAuth Authorization header
     * @param $header
     */
    public function oauthHeader(string $header = null): string
    {
        if (!empty($header)) {
            $this->authHeader = $header;
        }

        return $this->authHeader;
    }

    /**
     * @inheritdoc
     * @throws InvalidToken
     */
    public function setToken(mixed $token): static
    {
        $token = $this->configureToken($token);
        return parent::setToken($token);
    }

    /**
     * Configure the Expiration property on the token, based on the 'expires_in' property
     * @param $token
     */
    protected function configureToken(mixed &$token): mixed
    {
        $expiresIn = $this->getTokenProp(self::OAUTH_TOKEN_EXPIRES_IN, $token);
        $expiration = $this->getTokenProp(self::OAUTH_TOKEN_EXPIRATION, $token);
        if (!empty($expiresIn) && intval($expiresIn) > 0 && empty($expiration)) {
            $expiration = time() + ($expiresIn - 10);
            $token = $this->setTokenProp(self::OAUTH_TOKEN_EXPIRATION, $expiration, $token);
        }

        return $token;
    }

    /**
     * Get a specific property from the Token
     *
     * @param $prop
     * @return mixed|null
     */
    public function getTokenProp(string $prop, mixed &$token = null): mixed
    {
        $token = empty($token) && isset($this->token) ? $this->token : $token;
        $value = null;
        if (is_object($token) && isset($token->$prop)) {
            $value = $token->$prop;
        } elseif (is_array($token) && isset($token[$prop])) {
            $value = $token[$prop];
        }

        return $value;
    }

    /**
     * Get a specific property from the Token
     *
     * @param $prop
     * @return mixed|null
     */
    protected function setTokenProp(string $prop, mixed $value, mixed $token = null): mixed
    {
        $token = empty($token) && isset($this->token) ? $this->token : $token;
        if (is_object($token)) {
            $token->$prop = $value;
        } elseif (is_array($token)) {
            $token[$prop] = $value;
        }

        return $token;
    }

    /**
     *
     * @throws InvalidArgumentException
     */
    protected function cacheToken(): bool
    {
        $ttl = $this->getTokenProp(self::OAUTH_TOKEN_EXPIRES_IN);
        $expiration = $this->getTokenProp(self::OAUTH_TOKEN_EXPIRATION);
        if (!empty($expiration)) {
            $ttl = $expiration - time();
        }

        return $this->getCache()->set($this->getCacheKey(), $this->token, $ttl);
    }

    /**
     * @inheritdoc
     */
    public function configureRequest(Request $Request): Request
    {
        if ($this->isAuthenticated()) {
            return parent::configureRequest($Request);
        }

        return $Request;
    }

    /**
     * Get the Value to be set on the Auth Header
     */
    protected function getAuthHeaderValue(): string
    {
        return $this->authType . " " . $this->getTokenProp(self::OAUTH_TOKEN_ACCESS_TOKEN);
    }

    /**
     * Refreshes the OAuth 2 Token
     *
     */
    public function refresh(): bool
    {
        $res = false;
        if (!empty($this->getTokenProp(self::OAUTH_TOKEN_REFRESH_TOKEN))) {
            try {
                $Endpoint = $this->getActionEndpoint(self::ACTION_OAUTH_REFRESH);
                $Endpoint = $this->configureEndpoint($Endpoint, self::ACTION_OAUTH_REFRESH);
                $response = $Endpoint->execute()->getResponse();
                if ($response->getStatusCode() == 200) {
                    $token = $this->parseResponseToToken(self::ACTION_OAUTH_REFRESH, $response);
                    $this->setToken($token);
                    $res = true;
                }
            } catch (InvalidAuthenticationAction $exception) {
                $this->getLogger()->debug($exception->getMessage());
            } catch (RequestException $exception) {
                $response = $exception->getResponse();
                $statusCode = $response->getStatusCode();
                $message = $exception->getMessage();
                $content = $response->getBody()->getContents();
                if (!empty($content)) {
                    $message .= "RESPONSE: $content";
                }
                $this->getLogger()->error("[REST] OAuth Refresh Failed [$statusCode] - " . $message);
                // @codeCoverageIgnoreStart
            } catch (\Exception $exception) {
                $this->getLogger()->error("[REST] Unknown OAuth Refresh Exception - " . $exception->getMessage());
            }
            // @codeCoverageIgnoreEnd
        }

        return $res;
    }

    /**
     * Checks for Access Token property in token, and checks if Token is expired
     * @inheritdoc
     */
    public function isAuthenticated(): bool
    {
        if (parent::isAuthenticated() && !empty($this->getTokenProp(self::OAUTH_TOKEN_ACCESS_TOKEN))) {
            $expired = $this->isTokenExpired();
            //We err on the side of valid vs invalid, as the API will invalidate if we are wrong, which isn't harmful
            if ($expired === 0 || $expired === -1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if Token is expired based on 'expiration' flag on token
     * - Returns -1 if no expiration property is found
     */
    protected function isTokenExpired(): int
    {
        $expiration = $this->getTokenProp(self::OAUTH_TOKEN_EXPIRATION);
        if ($expiration) {
            if (time() > $expiration) {
                return 1;
            } else {
                return 0;
            }
        }

        return -1;
    }

    /**
     * @inheritdoc
     */
    protected function configureEndpoint(EndpointInterface $Endpoint, $action): EndpointInterface
    {
        return match ($action) {
            self::ACTION_OAUTH_REFRESH => $this->configureRefreshEndpoint($Endpoint),
            default => parent::configureEndpoint($Endpoint, $action),
        };
    }

    /**
     * Configure the Refresh Data based on Creds, Token, and Refresh Grant Type
     */
    protected function configureRefreshEndpoint(EndpointInterface $Endpoint): EndpointInterface
    {
        return $Endpoint->setData([
            'client_id' => $this->credentials['client_id'],
            'client_secret' => $this->credentials['client_secret'],
            'grant_type' => self::OAUTH_REFRESH_GRANT,
            'refresh_token' => $this->getTokenProp(self::OAUTH_TOKEN_REFRESH_TOKEN),
        ]);
    }

    /**
     * Add OAuth Grant Type for Auth
     * @inheritdoc
     */
    protected function configureAuthenticationEndpoint(EndpointInterface $Endpoint): EndpointInterface
    {
        $data = $this->credentials;
        $data['grant_type'] = $this->grant_type ?? static::$_DEFAULT_GRANT_TYPE;
        return $Endpoint->setData($data);
    }

    /**
     * @inheritDoc
     */
    public function reset(): static
    {
        $this->setGrantType(static::$_DEFAULT_GRANT_TYPE);
        return parent::reset();
    }

    /**
     * Parse token responses as string
     */
    protected function parseResponseToToken(string $action, Response $response): mixed
    {
        $tokenStr = $response->getBody()->getContents();
        $response->getBody()->rewind();
        try {
            $token = json_decode($tokenStr);
            if ($token === null && !empty($tokenStr)) {
                throw new \Exception("Invalid JSON string.");
            }
        } catch (\Exception $exception) {
            $this->getLogger()->critical("[REST] OAuth Token Parse Exception - " . $exception->getMessage());
        }

        return $token ?? $tokenStr;
    }
}
