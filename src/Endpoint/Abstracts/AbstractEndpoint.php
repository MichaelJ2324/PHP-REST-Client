<?php

namespace MRussell\REST\Endpoint\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use MRussell\REST\Endpoint\Data\DataInterface;
use MRussell\REST\Endpoint\Event\EventTriggerInterface;
use MRussell\REST\Endpoint\Event\Stack;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidUrl;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Endpoint\Traits\JsonHandlerTrait;

/**
 * Class AbstractEndpoint
 * @package MRussell\REST\Endpoint\Abstracts
 */
abstract class AbstractEndpoint implements EndpointInterface, EventTriggerInterface {
    use JsonHandlerTrait;
    
    const PROPERTY_URL = 'url';
    const PROPERTY_HTTP_METHOD = 'httpMethod';
    const PROPERTY_AUTH = 'auth';

    const EVENT_CONFIGURE_METHOD = 'configure_method';
    const EVENT_CONFIGURE_URL = 'configure_url';
    const EVENT_CONFIGURE_PAYLOAD = 'configure_payload';
    const EVENT_AFTER_CONFIGURED_REQUEST = 'after_configure_req';
    const EVENT_AFTER_RESPONSE = 'after_response';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Stack
     */
    private $eventStack;

    protected static $_DEFAULT_PROPERTIES = array(
        self::PROPERTY_URL => '',
        self::PROPERTY_HTTP_METHOD => '',
        self::PROPERTY_AUTH => false
    );

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
     * Associative array of properties that define an Endpoint
     * @var array
     */
    protected $properties = array();

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
    public function setProperties(array $properties): void {
        if (!isset($properties[self::PROPERTY_HTTP_METHOD])) {
            $properties[self::PROPERTY_HTTP_METHOD] = '';
        }
        if (!isset($properties[self::PROPERTY_URL])) {
            $properties[self::PROPERTY_URL] = '';
        }
        if (!isset($properties[self::PROPERTY_AUTH])) {
            $properties[self::PROPERTY_AUTH] = false;
        }
        $this->properties = $properties;
    }

    /**
     * @inheritdoc
     */
    public function setProperty($name, $value): EndpointInterface {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): array {
        return $this->properties;
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
    public function setData($data): EndpointInterface {
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
     * @inheritdoc
     */
    public function getRequest(): Request {
        return $this->request;
    }

    /**
     * @param Response $response
     * @return $this|EndpointInterface
     */
    protected function setResponse(Response $response) {
        $this->response = $response;
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
     * @inheritDoc
     */
    public function setHttpClient(Client $client): EndpointInterface {
        $this->client = $client;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHttpClient(): Client {
        return $this->client == null ? new Client() : $this->client;
    }

    /**
     * @inheritdoc
     * @param array $options Guzzle Send Options
     * @return $this
     */
    public function execute(array $options = []): EndpointInterface {
        try {
            $this->setResponse($this->getHttpClient()->send($this->buildRequest(), $options));
        } catch (\GuzzleHttp\Exception\RequestException $e){
            throw new \MRussell\REST\Exception\Endpoint\InvalidRequest($e->getMessage());
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @param null $data - short form data for Endpoint, which is configure by configureData method
     * @return $this
     */
    public function asyncExecute(array $options = []): EndpointInterface {
        $request = $this->buildRequest();
        $promise = $this->getHttpClient()->sendAsync($request, $options);
        $endpoint = $this;
        $promise->then(
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
     * @inheritdoc
     */
    public function authRequired(): bool {
        $required = false;
        if (isset($this->properties[self::PROPERTY_AUTH])) {
            $required = $this->properties[self::PROPERTY_AUTH];
        }
        return $required;
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
        $this->request = new Request($method, $url);
        return $this->configureRequest($this->request, $data);
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
        if (is_array($data)) {
            $data = json_encode($data);
        }
        switch ($request->getMethod()) {
            case "GET":
                if (is_string($data) || is_array($data)) {
                    $request = new Request($request->getMethod(), $request->getUri(), [
                        'query' => $data
                    ]);
                    break;
                }
            default:
                $request = $request->withBody(Utils::streamFor($data));
        }
        $this->request = $request;
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
     * @inheritDoc
     */
    public function triggerEvent(string $event, &$data = null): void {
        $this->eventStack->trigger($event, $data);
    }

    /**
     * @inheritDoc
     */
    public function onEvent(string $event, callable $func, string $id = null) {
        return $this->eventStack->register($event, $func, $id);
    }

    /**
     * @inheritDoc
     */
    public function offEvent(string $event, $id): bool {
        return $this->eventStack->remove($event, $id);
    }
}
