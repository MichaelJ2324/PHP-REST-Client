<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use MRussell\REST\Client\ClientAwareTrait;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Event\EventTriggerInterface;
use MRussell\REST\Endpoint\Event\Stack;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Endpoint\Traits\EventsTrait;
use MRussell\REST\Endpoint\Traits\PropertiesTrait;
use MRussell\REST\Exception\Endpoint\InvalidUrl;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Traits\JsonHandlerTrait;

/**
 * Class AbstractEndpoint
 * @package MRussell\REST\Endpoint\Abstracts
 */
abstract class AbstractEndpoint implements EndpointInterface, EventTriggerInterface
{
    use EventsTrait;
    use ClientAwareTrait;
    use JsonHandlerTrait;
    use PropertiesTrait {
        setProperties as rawSetProperties;
    }

    public const PROPERTY_URL = 'url';

    public const PROPERTY_HTTP_METHOD = 'httpMethod';

    public const PROPERTY_AUTH = 'auth';

    public const EVENT_CONFIGURE_METHOD = 'configure_method';

    public const EVENT_CONFIGURE_URL = 'configure_url';

    public const EVENT_CONFIGURE_PAYLOAD = 'configure_payload';

    public const EVENT_AFTER_CONFIGURED_REQUEST = 'after_configure_req';

    public const EVENT_AFTER_RESPONSE = 'after_response';

    public const AUTH_NOAUTH = 0;

    public const AUTH_EITHER = 1;

    public const AUTH_REQUIRED = 2;

    public const URL_VAR_CHAR = '$';

    public const URL_OPTIONAL_VAR_CHAR = ':';

    protected static array $_DEFAULT_PROPERTIES = [
        self::PROPERTY_URL => '',
        self::PROPERTY_HTTP_METHOD => '',
        self::PROPERTY_AUTH => self::AUTH_EITHER,
    ];

    private PromiseInterface $promise;

    /**
     * The Variable Identifier to parse Endpoint URL
     */
    protected static string $_URL_VAR_CHARACTER = '$';

    /**
     * The initial URL passed into the Endpoint
     */
    protected string $_baseUrl = '';

    /**
     * The passed in Options for the Endpoint, mainly used for parsing URL Variables
     */
    protected array $_urlArgs = [];

    /**
     * The data being passed to the API Endpoint.
     * Defaults to Array, but can be mixed based on how you want to use Endpoint.
     */
    protected string|array|\ArrayAccess|null $_data;

    /**
     * The Request Object used by the Endpoint to submit the data
     */
    protected Request $_request;

    /**
     * The Response Object used by the Endpoint
     */
    protected Response $_response;

    protected bool $_catchNon200Responses = false;

    public function __construct(array $properties = [], array $urlArgs = [])
    {
        $this->_eventStack = new Stack();
        $this->_eventStack->setEndpoint($this);
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        if (!empty($urlArgs)) {
            $this->setUrlArgs($urlArgs);
        }

        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    public function catchNon200Responses(bool $catch = true): static
    {
        $this->_catchNon200Responses = $catch;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setUrlArgs(array $args): static
    {
        if (!empty($args) && $this->needsUrlArgs()) {
            $args = $this->normalizeUrlArgs($args);
        }

        $this->_urlArgs = $args;
        return $this;
    }

    protected function normalizeUrlArgs(array $urlArgs): array
    {
        $vars = $this->extractUrlVariables();
        $argNum = 0;
        $normalized = [];
        foreach ($urlArgs as $key => $value) {
            if (isset($vars[$key])) {
                $normalized[$key] = $value;
                if ($vars[$key]['index'] == $argNum) {
                    $argNum++;
                }
            } elseif (is_numeric($key) && !empty($value)) {
                foreach ($vars as $var => $varProps) {
                    if (!isset($normalized[$var]) && !isset($urlArgs[$var])) {
                        if ($varProps['index'] == $key) {
                            $normalized[$var] = $value;
                            break;
                        } elseif ($varProps['index'] == $argNum) {
                            $normalized[$var] = $value;
                            break;
                        }
                    }
                }
                $argNum++;
            }
        }

        return $normalized;
    }

    /**
     * @inheritdoc
     */
    public function getUrlArgs(): array
    {
        return $this->_urlArgs;
    }

    /**
     * @inheritdoc
     */
    public function setBaseUrl($url): static
    {
        $this->_baseUrl = $url;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBaseUrl(): string
    {
        if (empty($this->_baseUrl) && isset($this->_client)) {
            return $this->getClient()->getAPIUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function getEndPointUrl(bool $full = false): string
    {
        $url = $this->getProperty(self::PROPERTY_URL) ?? "";

        if ($full) {
            $url = rtrim($this->getBaseUrl(), '/') . ('/' . $url);
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function setData(string|array|\ArrayAccess|null $data): static
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData(): string|array|\ArrayAccess|null
    {
        return $this->_data ?? null;
    }

    /**
     * @return $this|EndpointInterface
     */
    protected function setResponse(Response $_response): static
    {
        $this->_response = $_response;
        $this->_respContent = null;
        $this->triggerEvent(self::EVENT_AFTER_RESPONSE, $_response);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponse(): Response|null
    {
        return $this->_response ?? null;
    }

    public function getResponseBody(bool $associative = true): mixed
    {
        $response = $this->getResponse();
        return $response instanceof Response ? $this->getResponseContent($response, $associative) : null;
    }

    public function getHttpClient(): Client
    {
        if (isset($this->_client)) {
            return $this->getClient()->getHttpClient();
        }

        return new Client();
    }

    /**
     *
     * @inheritdoc
     * @param array $options Guzzle Send Options
     * @return $this
     * @throws GuzzleException
     */
    public function execute(array $options = []): static
    {
        try {
            $response = $this->getHttpClient()->send($this->buildRequest(), $options);
            $this->setResponse($response);
        } catch (RequestException $requestException) {
            $response = $requestException->getResponse();
            if ($response instanceof Response) {
                $this->setResponse($requestException->getResponse());
            }

            if (!$this->_catchNon200Responses) {
                throw $requestException;
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @param null $data - short form data for Endpoint, which is configure by configureData method
     * @return $this
     */
    public function asyncExecute(array $options = []): static
    {
        $request = $this->buildRequest();
        $this->promise = $this->getHttpClient()->sendAsync($request, $options);
        $this->promise->then(
            function (Response $res) use ($options): void {
                $this->setResponse($res);
                if (isset($options['success']) && is_callable($options['success'])) {
                    $options['success']($res);
                }
            },
            function (RequestException $e) use ($options): void {
                $response = $e->getResponse();
                if ($response instanceof Response) {
                    $this->setResponse($response);
                }

                if (isset($options['error']) && is_callable($options['error'])) {
                    $options['error']($e);
                }
            },
        );
        return $this;
    }

    public function getPromise(): ?PromiseInterface
    {
        return $this->promise ?? null;
    }

    /**
     * @inheritdoc
     */
    public function useAuth(): int
    {
        $auth = self::AUTH_EITHER;
        if (isset($this->_properties[self::PROPERTY_AUTH])) {
            $auth = intval($this->_properties[self::PROPERTY_AUTH]);
        }

        return $auth;
    }

    public function getMethod(): string
    {
        $this->triggerEvent(self::EVENT_CONFIGURE_METHOD);
        if (
            isset($this->_properties[self::PROPERTY_HTTP_METHOD]) &&
            $this->_properties[self::PROPERTY_HTTP_METHOD] !== ''
        ) {
            return $this->_properties[self::PROPERTY_HTTP_METHOD];
        }

        return "GET";
    }

    /**
     * Verifies URL and Data are setup, then sets them on the Request Object
     */
    public function buildRequest(): Request
    {
        $method = $this->getMethod();
        $url = $this->configureURL($this->getUrlArgs());
        if ($this->verifyUrl($url)) {
            $url = rtrim($this->getBaseUrl(), "/") . "/" . $url;
        }

        $data = $this->configurePayload();
        $request = new Request($method, $url);
        $request = $this->configureJsonRequest($request);

        $this->_request = $this->configureRequest($request, $data);
        return $this->_request;
    }

    /**
     * Configures Data on the Endpoint to be set on the Request.
     * @return string|array|DataInterface|null|Stream
     */
    protected function configurePayload(): mixed
    {
        $data = $this->getData() ?? null;
        $this->triggerEvent(self::EVENT_CONFIGURE_PAYLOAD, $data);
        return $data;
    }

    /**
     * @param $data
     */
    protected function configureRequest(Request $request, $data): Request
    {
        if ($data !== null) {
            switch ($request->getMethod()) {
                case "GET":
                    if (!empty($data)) {
                        $value = $data;
                        if (\is_array($value)) {
                            $value = \http_build_query($value, '', '&', \PHP_QUERY_RFC3986);
                        }

                        if (!\is_string($value)) {
                            throw new InvalidArgumentException('query must be a string or array');
                        }

                        $uri = $request->getUri()->withQuery($value);
                        $request = $request->withUri($uri);
                    }

                    break;
                default:
                    if (is_array($data)) {
                        $data = json_encode($data);
                    }

                    $request = $request->withBody(Utils::streamFor($data));
            }
        }

        $args = ['request' => $request, 'data' => $data];
        $this->triggerEvent(self::EVENT_AFTER_CONFIGURED_REQUEST, $args);
        return $args['request'];
    }

    /**
     * Configures the URL, by updating any variable placeholders in the URL property on the Endpoint
     * - Replaces $var $options['var']
     * - If $options['var'] doesn't exist, replaces with next numeric option in array
     */
    protected function configureURL(array $urlArgs): string
    {
        $url = $this->getEndPointUrl();
        $this->triggerEvent(self::EVENT_CONFIGURE_URL, $urlArgs);
        if ($this->needsUrlArgs()) {
            $url = $this->populateUrlWithArgs($url, $urlArgs);
        }

        return $url;
    }

    protected function populateUrlWithArgs(string $url, array $urlArgs): string
    {
        $urlArgs = $this->normalizeUrlArgs($urlArgs);
        $variables = $this->extractUrlVariables($url);
        $urlArr = explode("/", trim($url, "/"));
        foreach ($variables as $variable => $props) {
            $index = $props['index'];
            $replace = $urlArgs[$index] ?? "";
            if (isset($urlArgs[$variable])) {
                $replace = $urlArgs[$variable];
            }

            $pattern = preg_quote(static::URL_VAR_CHAR . static::URL_OPTIONAL_VAR_CHAR, "/") . "?" . preg_quote($variable, "/");
            if (empty($replace) && $props['optional']) {
                foreach ($urlArr as $i => $urlPart) {
                    if (preg_match(sprintf('/%s/', $pattern), $urlPart)) {
                        $urlArr = array_slice($urlArr, 0, $i);
                        break;
                    }
                }
            } elseif (!empty($replace)) {
                $urlArr = preg_replace(sprintf('/%s/', $pattern), $replace, $urlArr);
            }
        }

        return rtrim(implode("/", $urlArr), "/");
    }

    /**
     * Verify if URL is configured properly
     * @throws InvalidUrl
     */
    private function verifyUrl(string $url): bool
    {
        if (str_contains($url, static::URL_VAR_CHAR)) {
            throw new InvalidUrl([static::class, $url]);
        }

        return true;
    }

    /**
     * Checks if Endpoint URL requires Arguments
     */
    protected function needsUrlArgs(): bool
    {
        $url = $this->getEndPointUrl();
        $variables = $this->extractUrlVariables($url);
        return !empty($variables);
    }

    /**
     * Helper method for extracting variables via Regex from a passed in URL
     */
    protected function extractUrlVariables(string $url = null): array
    {
        $url = $url ?? $this->getEndPointUrl();
        $variables = [];
        $varChar = preg_quote(static::URL_VAR_CHAR, "/");
        $urlArr = explode("/", trim($url, "/"));
        $varIndex = 0;
        foreach ($urlArr as $pathPart) {
            $pattern = "/(" . $varChar . sprintf('[^%s]+)/', $varChar);
            if (preg_match_all($pattern, $pathPart, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $optional = str_contains($match[0], static::URL_OPTIONAL_VAR_CHAR);
                    $var = str_replace([static::URL_VAR_CHAR, static::URL_OPTIONAL_VAR_CHAR], '', $match[0]);
                    if (!isset($variables[$var])) {
                        $variables[$var] = [
                            'index' => $varIndex,
                            'optional' => $optional,
                        ];
                        $varIndex++;
                    } else {
                        $variables[$var]['optional'] = $optional ? $variables[$var]['optional'] : $optional;
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * @return $this
     */
    public function reset(): static
    {
        unset($this->_request);
        unset($this->_response);
        $this->_urlArgs = [];
        $this->setData(null);
        $this->setProperties([]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $properties): static
    {
        if (!isset($properties[self::PROPERTY_HTTP_METHOD])) {
            $properties[self::PROPERTY_HTTP_METHOD] = '';
        }

        if (!isset($properties[self::PROPERTY_URL])) {
            $properties[self::PROPERTY_URL] = '';
        }

        if (!isset($properties[self::PROPERTY_AUTH])) {
            $properties[self::PROPERTY_AUTH] = self::AUTH_EITHER;
        } else {
            $properties[self::PROPERTY_AUTH] = intval($properties[self::PROPERTY_AUTH]);
        }

        return $this->rawSetProperties($properties);
    }
}
