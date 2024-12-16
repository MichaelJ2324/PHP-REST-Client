<?php

namespace MRussell\REST\Auth\Abstracts;

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

    /**
     * @var string
     */
    protected static $_DEFAULT_GRANT_TYPE = self::OAUTH_CLIENT_CREDENTIALS_GRANT;

    /**
     * @inheritdoc
     */
    protected static $_AUTH_TYPE = self::DEFAULT_AUTH_TYPE;

    /**
     * @inheritdoc
     */
    protected static array $_DEFAULT_AUTH_ACTIONS = [self::ACTION_AUTH, self::ACTION_LOGOUT, self::ACTION_OAUTH_REFRESH];

    /**
     * The OAuth2 Full token
     * @var array
     */
    protected $token = [];

    /**
     * @var
     */
    protected $grant_type;

    public function __construct()
    {
        parent::__construct();
        $this->setGrantType(static::$_DEFAULT_GRANT_TYPE);
    }

    /**
     * @param $grant_type
     * @return $this
     */
    public function setGrantType($grant_type): AuthControllerInterface
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
    public static function oauthHeader($header = null): string
    {
        if ($header !== null) {
            static::$_AUTH_HEADER = $header;
        }
        return static::$_AUTH_HEADER;
    }

    /**
     * @inheritdoc
     * @throws InvalidToken
     */
    public function setToken($token)
    {
        if (is_array($token) && isset($token['access_token'])) {
            $token = $this->configureToken($token);
            return parent::setToken($token);
        } elseif (is_object($token) && $token->access_token) {
            $token = $this->configureToken($token);
            return parent::setToken($token);
        }
        throw new InvalidToken();
    }

    /**
     * Configure the Expiration property on the token, based on the 'expires_in' property
     * @param $token
     * @return mixed
     */
    protected function configureToken($token)
    {
        if (is_array($token)) {
            if (isset($token['expires_in']) && !isset($token['expiration'])) {
                $token['expiration'] = time() + ($token['expires_in'] - 30);
            }
        } elseif (is_object($token)) {
            if (isset($token->expires_in) && !isset($token->expiration)) {
                $token->expiration = time() + ($token->expires_in - 30);
            }
        }

        return $token;
    }

    /**
     * Get a specific property from the Token
     *
     * @param $prop
     * @return mixed|null
     */
    public function getTokenProp($prop)
    {
        if ($this->token) {
            if (is_object($this->token) && $this->token->$prop) {
                return $this->token->$prop;
            } elseif (is_array($this->token) && isset($this->token[$prop])) {
                return $this->token[$prop];
            }
        }
        return null;
    }

    /**
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function cacheToken(): bool
    {
        return $this->getCache()->set($this->getCacheKey(), $this->token, $this->getTokenProp('expires_in'));
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
        return static::$_AUTH_TYPE . " " . $this->getTokenProp('access_token');
    }

    /**
     * Refreshes the OAuth 2 Token
     *
     */
    public function refresh(): bool
    {
        $res = false;
        if (!empty($this->getTokenProp('refresh_token'))) {
            try {
                $Endpoint = $this->getActionEndpoint(self::ACTION_OAUTH_REFRESH);
                $Endpoint = $this->configureEndpoint($Endpoint, self::ACTION_OAUTH_REFRESH);
                $response = $Endpoint->execute()->getResponse();
                if ($response->getStatusCode() == 200) {
                    $token = $this->parseResponseToToken(self::ACTION_OAUTH_REFRESH, $response);
                    $this->setToken($token);
                    $res = true;
                }
            } catch (InvalidAuthenticationAction $ex) {
                $this->getLogger()->debug($ex->getMessage());
            } catch (\Exception $ex) {
                $this->getLogger()->error("[REST] OAuth Refresh Exception - " . $ex->getMessage());
            }
        }
        return $res;
    }

    /**
     * Checks for Access Token property in token, and checks if Token is expired
     * @inheritdoc
     */
    public function isAuthenticated(): bool
    {
        if (parent::isAuthenticated() && !empty($this->getTokenProp('access_token'))) {
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
        $expiration = $this->getTokenProp('expiration');
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
        switch ($action) {
            case self::ACTION_OAUTH_REFRESH:
                return $this->configureRefreshEndpoint($Endpoint);
            default:
                return parent::configureEndpoint($Endpoint, $action);
        }
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
            'refresh_token' => $this->getTokenProp('refresh_token'),
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
    public function reset(): AuthControllerInterface
    {
        $this->setGrantType(static::$_DEFAULT_GRANT_TYPE);
        return parent::reset();
    }

    /**
     * Parse token responses as string
     *
     * @return mixed
     */
    protected function parseResponseToToken(string $action, Response $response)
    {
        $tokenStr = $response->getBody()->getContents();
        $response->getBody()->rewind();
        try {
            $token = json_decode($tokenStr);
            if ($token === null && !empty($tokenStr)) {
                throw new \Exception("Invalid JSON string.");
            }
        } catch (\Exception $ex) {
            $this->getLogger()->critical("[REST] OAuth Token Parse Exception - " . $ex->getMessage());
        }
        return $token ?? $tokenStr;
    }
}
