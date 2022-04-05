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
abstract class AbstractEndpoint implements EndpointInterface, EventTriggerInterface {
    use EventsTrait,
        ClientAwareTrait,
        JsonHandlerTrait;
    use PropertiesTrait {
        setProperties as rawSetProperties;
    }
    
    const PROPERTY_URL = 'url';
    const PROPERTY_HTTP_METHOD = 'httpMethod';
    const PROPERTY_AUTH = 'auth';

    const EVENT_CONFIGURE_METHOD = 'configure_method';
    const EVENT_CONFIGURE_URL = 'configure_url';
    const EVENT_CONFIGURE_PAYLOAD = 'configure_payload';
    const EVENT_AFTER_CONFIGURED_REQUEST = 'after_configure_req';
    const EVENT_AFTER_RESPONSE = 'after_response';

    const AUTH_NOAUTH = 0;
    const AUTH_EITHER = 1;
    const AUTH_REQUIRED = 2;

    protected static $_DEFAULT_PROPERTIES = array(
        self::PROPERTY_URL => '',
        self::PROPERTY_HTTP_METHOD => '',
        self::PROPERTY_AUTH => self::AUTH_EITHER
    );

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
    protected $urlArgs = array();

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

    public function __construct(array $options = array(), array $properties = array()) {
        $this->eventStack = new Stack();
        $this->eventStack->setEndpoint($this);
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        if (!empty($options)) {
            $this->setUrlArgs($options);
        }
        if (!empty($properties)) {
            foreach ($properties as $key => $value) {
                $this->setProperty($key, $value);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function setUrlArgs(array $args): EndpointInterface {
        $this->urlArgs = $args;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUrlArgs(): array {
        return $this->urlArgs;
    }

    /**
     * @inheritdoc
     */
    public function setBaseUrl($url): EndpointInterface {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBaseUrl(): string {
        if (empty($this->baseUrl) && $this->client){
            return $this->getClient()->getAPIUrl();
        }
        return $this->baseUrl;
    }

    /**
     * @inheritdoc
     */
    public function getEndPointUrl($full = false): string {
        $url = static::$_ENDPOINT_URL;
        if (isset($this->properties[self::PROPERTY_URL]) && $this->properties[self::PROPERTY_URL] !== '') {
            $url = $this->properties[self::PROPERTY_URL];
        }
        if ($full) {
            $url = rtrim($this->getBaseUrl(), '/') . "/$url";
        }
        return $url;
    }

    /**
     * @inheritdoc
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Due to how Guzzle Requests work, this may not return the actual Request object used
     * - Use Middleware::history() if you need the request that was sent to server
     *
     * May deprecate in the future, just leaving it in right now to assess if its still needed
     * TODO:Deprecate me
     * @return Request
     * @codeCoverageIgnore
     */
    protected function getRequest(): Request {
        return $this->request;
    }

    /**
     * @param Response $response
     * @return $this|EndpointInterface
     */
    protected function setResponse(Response $response) {
        $this->response = $response;
        $this->respBody = null;
        $this->triggerEvent(self::EVENT_AFTER_RESPONSE, $response);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponse(): Response {
        return $this->response;
    }

    /**
     * @return Client
     */
    public function getHttpClient(): Client {
        return !$this->client? new Client() : $this->getClient()->getHttpClient();
    }

    /**
     *
     * @inheritdoc
     * @param array $options Guzzle Send Options
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(array $options = []): EndpointInterface {
        $this->setResponse($this->getHttpClient()->send($this->buildRequest(), $options));
        return $this;
    }

    /**
     * @inheritdoc
     * @param null $data - short form data for Endpoint, which is configure by configureData method
     * @return $this
     */
    public function asyncExecute(array $options = []): EndpointInterface {
        $request = $this->buildRequest();
        $this->promise = $this->getHttpClient()->sendAsync($request, $options);
        $endpoint = $this;
        $this->promise->then(
            function (Response $res) use ($endpoint, $options) {
                $endpoint->setResponse($res);
                if (is_callable($options['success'])) {
                    $options['success']($res);
                }
            },
            function (RequestException $e) use ($options) {
                if (is_callable($options['error'])) {
                    $options['error']($e);
                }
            }
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
    public function useAuth(): int {
        $auth = self::AUTH_EITHER;
        if (isset($this->properties[self::PROPERTY_AUTH])) {
            $auth = intval($this->properties[self::PROPERTY_AUTH]);
        }
        return $auth;
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        $this->triggerEvent(self::EVENT_CONFIGURE_METHOD);
        if (
            isset($this->properties[self::PROPERTY_HTTP_METHOD]) &&
            $this->properties[self::PROPERTY_HTTP_METHOD] !== ''
        ) {
            return $this->properties[self::PROPERTY_HTTP_METHOD];
        }
        return "GET";
    }

    /**
     * Verifies URL and Data are setup, then sets them on the Request Object
     * @return Request
     */
    public function buildRequest(): Request {
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
    protected function configurePayload() {
        $data = $this->getData() ?? null;
        $this->triggerEvent(self::EVENT_CONFIGURE_PAYLOAD, $data);
        return $data;
    }

    /**
     * @param Request $request
     * @param $data
     * @return Request
     */
    protected function configureRequest(Request $request, $data): Request {
        if ($data !== null){
            switch ($request->getMethod()) {
                case "GET":
                    if (!empty($data)){
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
        $args = array(
            'request' => $request,
            'data' => $data
        );
        $this->triggerEvent(self::EVENT_AFTER_CONFIGURED_REQUEST, $args);
        return $args['request'];
    }

    /**
     * Configures the URL, by updating any variable placeholders in the URL property on the Endpoint
     * - Replaces $var $options['var']
     * - If $options['var'] doesn't exist, replaces with next numeric option in array
     * @param array $options
     * @return string
     */
    protected function configureURL(array $options): string {
        $url = $this->getEndPointUrl();
        $this->triggerEvent(self::EVENT_CONFIGURE_URL, $options);
        if ($this->hasUrlArgs()) {
            $urlArr = explode("/", $url);
            $optional = false;
            $optionNum = 0;
            $keys = array_keys($options);
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
                    $opt = str_replace(array(static::$_URL_VAR_CHARACTER, ':'), '', $urlPart);
                    if (isset($options[$opt])) {
                        $replace = $options[$opt];
                    }
                    if (isset($options[$optionNum]) && ($replace == '' || $replace == null)) {
                        $replace = $options[$optionNum];
                        $optionNum = $optionNum + 1;
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
     * @param string $url
     * @return bool
     * @throws InvalidUrl
     */
    private function verifyUrl(string $url): bool {
        if (strpos($url, static::$_URL_VAR_CHARACTER) !== false) {
            throw new InvalidUrl(array(get_class($this), $url));
        }
        return true;
    }

    /**
     * Checks if Endpoint URL requires Arguments
     * @return bool
     */
    protected function hasUrlArgs(): bool {
        $url = $this->getEndPointUrl();
        $variables = $this->extractUrlVariables($url);
        return !empty($variables);
    }

    /**
     * Helper method for extracting variables via Regex from a passed in URL
     * @param $url
     * @return array
     */
    protected function extractUrlVariables($url): array {
        $variables = array();
        $pattern = "/(\\" . static::$_URL_VAR_CHARACTER . ".*?[^\\/]*)/";
        if (preg_match($pattern, $url, $matches)) {
            array_shift($matches);
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
        unset($this->request);
        unset($this->response);
        $this->urlArgs = [];
        $this->setData(null);
        $this->setProperties([]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setProperties(array $properties) {
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
