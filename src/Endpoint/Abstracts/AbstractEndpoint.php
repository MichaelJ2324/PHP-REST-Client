<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
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

    protected static $_DEFAULT_PROPERTIES = [self::PROPERTY_URL => '', self::PROPERTY_HTTP_METHOD => '', self::PROPERTY_AUTH => self::AUTH_EITHER];

    /**
     * @var Promise
     */
    private $promise;

    /**
     * The Variable Identifier to parse Endpoint URL
     * @var string
     */
    protected static $_URL_VAR_CHARACTER = '$';

    /**
     * The Endpoint Relative URL to the API
     * @var string
     */
    protected static $_ENDPOINT_URL = '';

    /**
     * The initial URL passed into the Endpoint
     * @var string
     */
    protected $baseUrl = '';

    /**
     * The passed in Options for the Endpoint, mainly used for parsing URL Variables
     * @var array
     */
    protected $urlArgs = [];

    /**
     * The data being passed to the API Endpoint.
     * Defaults to Array, but can be mixed based on how you want to use Endpoint.
     * @var mixed
     */
    protected $data;

    /**
     * The Request Object used by the Endpoint to submit the data
     * @var Request
     */
    protected $request;

    /**
     * The Response Object used by the Endpoint
     * @var Response
     */
    protected $response;

    public function __construct(array $urlArgs = [], array $properties = [])
    {
        $this->eventStack = new Stack();
        $this->eventStack->setEndpoint($this);
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        if (!empty($urlArgs)) {
            $this->setUrlArgs($urlArgs);
        }

        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function setUrlArgs(array $args): EndpointInterface
    {
        $this->urlArgs = $args;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUrlArgs(): array
    {
        return $this->urlArgs;
    }

    /**
     * @inheritdoc
     */
    public function setBaseUrl($url): EndpointInterface
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBaseUrl(): string
    {
        if (empty($this->baseUrl) && $this->client) {
            return $this->getClient()->getAPIUrl();
        }

        return $this->baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function getEndPointUrl($full = false): string
    {
        $url = static::$_ENDPOINT_URL;
        if (isset($this->_properties[self::PROPERTY_URL]) && $this->_properties[self::PROPERTY_URL] !== '') {
            $url = $this->_properties[self::PROPERTY_URL];
        }

        if ($full) {
            $url = rtrim($this->getBaseUrl(), '/') . ('/' . $url);
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Due to how Guzzle Requests work, this may not return the actual Request object used
     * - Use Middleware::history() if you need the request that was sent to server
     *
     * May deprecate in the future, just leaving it in right now to assess if its still needed
     * TODO:Deprecate me
     * @codeCoverageIgnore
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return $this|EndpointInterface
     */
    protected function setResponse(Response $response)
    {
        $this->response = $response;
        $this->respContent = null;
        $this->triggerEvent(self::EVENT_AFTER_RESPONSE, $response);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return mixed|null
     */
    public function getResponseBody(bool $associative = true)
    {
        $response = $this->getResponse();
        return $response ? $this->getResponseContent($response, $associative) : null;
    }

    public function getHttpClient(): Client
    {
        return $this->client ? $this->getClient()->getHttpClient() : new Client();
    }

    /**
     *
     * @inheritdoc
     * @param array $options Guzzle Send Options
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(array $options = []): EndpointInterface
    {
        $this->setResponse($this->getHttpClient()->send($this->buildRequest(), $options));
        return $this;
    }

    /**
     * @inheritdoc
     * @param null $data - short form data for Endpoint, which is configure by configureData method
     * @return $this
     */
    public function asyncExecute(array $options = []): EndpointInterface
    {
        $request = $this->buildRequest();
        $this->promise = $this->getHttpClient()->sendAsync($request, $options);
        $endpoint = $this;
        $this->promise->then(
            function (Response $res) use ($endpoint, $options) {
                $endpoint->setResponse($res);
                if (isset($options['success']) && is_callable($options['success'])) {
                    $options['success']($res);
                }
            },
            function (RequestException $e) use ($options) {
                if (isset($options['error']) && is_callable($options['error'])) {
                    $options['error']($e);
                }
            },
        );
        return $this;
    }

    /**
     * @return Promise
     */
    public function getPromise()
    {
        return $this->promise;
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
        $this->request = $this->configureRequest($request, $data);
        return $this->request;
    }

    /**
     * Configures Data on the Endpoint to be set on the Request.
     * @return string|array|DataInterface|null|Stream
     */
    protected function configurePayload()
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
        if ($this->hasUrlArgs()) {
            $urlArr = explode("/", $url);
            $optional = false;
            $optionNum = 0;
            $keys = array_keys($urlArgs);
            sort($keys);
            foreach ($keys as $key) {
                if (is_numeric($key)) {
                    $optionNum = $key;
                    break;
                }
            }

            foreach ($urlArr as $key => $urlPart) {
                $replace = null;
                if (strpos($urlPart, static::$_URL_VAR_CHARACTER) !== false) {
                    if (strpos($urlPart, ':') !== false) {
                        $optional = true;
                        $replace = '';
                    }

                    $opt = str_replace([static::$_URL_VAR_CHARACTER, ':'], '', $urlPart);
                    if (isset($urlArgs[$opt])) {
                        $replace = $urlArgs[$opt];
                    }

                    if (isset($urlArgs[$optionNum]) && ($replace == '' || $replace == null)) {
                        $replace = $urlArgs[$optionNum];
                        $optionNum += 1;
                    }

                    if ($optional && $replace == '') {
                        $urlArr = array_slice($urlArr, 0, $key);
                        break;
                    }

                    if ($replace !== null) {
                        $urlArr[$key] = $replace;
                    }
                }
            }

            $url = implode("/", $urlArr);
            $url = rtrim($url, "/");
        }

        return $url;
    }

    /**
     * Verify if URL is configured properly
     * @throws InvalidUrl
     */
    private function verifyUrl(string $url): bool
    {
        if (strpos($url, static::$_URL_VAR_CHARACTER) !== false) {
            throw new InvalidUrl([get_class($this), $url]);
        }

        return true;
    }

    /**
     * Checks if Endpoint URL requires Arguments
     */
    protected function hasUrlArgs(): bool
    {
        $url = $this->getEndPointUrl();
        $variables = $this->extractUrlVariables($url);
        return !empty($variables);
    }

    /**
     * Helper method for extracting variables via Regex from a passed in URL
     * @param $url
     */
    protected function extractUrlVariables($url): array
    {
        $variables = [];
        $pattern = "/(\\" . static::$_URL_VAR_CHARACTER . ".*?[^\\/]*)/";
        if (preg_match_all($pattern, $url, $matches)) {
            foreach ($matches as $match) {
                $variables[] = $match[0];
            }
        }

        return $variables;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->request = null;
        $this->response = null;
        $this->urlArgs = [];
        $this->setData(null);
        $this->setProperties([]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $properties)
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
