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
    protected string $baseUrl = '';

    /**
     * The passed in Options for the Endpoint, mainly used for parsing URL Variables
     */
    protected array $urlArgs = [];

    /**
     * The data being passed to the API Endpoint.
     * Defaults to Array, but can be mixed based on how you want to use Endpoint.
     */
    protected string|array|\ArrayAccess|null $data;

    /**
     * The Request Object used by the Endpoint to submit the data
     */
    protected Request $request;

    /**
     * The Response Object used by the Endpoint
     */
    protected Response $response;

    protected bool $catchNon200Responses = false;

    public function __construct(array $properties = [], array $urlArgs = [])
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

    public function catchNon200Responses(bool $catch = true): static
    {
        $this->catchNon200Responses = $catch;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setUrlArgs(array $args): static
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
    public function setBaseUrl($url): static
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
        $this->data = $data;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData(): string|array|\ArrayAccess|null
    {
        return $this->data ?? null;
    }

    /**
     * @return $this|EndpointInterface
     */
    protected function setResponse(Response $response): static
    {
        $this->response = $response;
        $this->respContent = null;
        $this->triggerEvent(self::EVENT_AFTER_RESPONSE, $response);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getResponse(): Response
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
     * @throws GuzzleException
     */
    public function execute(array $options = []): static
    {
        try {
            $response = $this->getHttpClient()->send($this->buildRequest(), $options);
            $this->setResponse($response);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            if ($response instanceof Response) {
                $this->setResponse($exception->getResponse());
            }
            if (!$this->catchNon200Responses) {
                throw $exception;
            }
        }
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
        $this->promise->then(
            function (Response $res) use ($options): void {
                $this->setResponse($res);
                if (isset($options['success']) && is_callable($options['success'])) {
                    $options['success']($res);
                }
            },
            function (RequestException $e) use ($options): void {
                $this->setResponse($e->getResponse());
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
                if (str_contains($urlPart, static::$_URL_VAR_CHARACTER)) {
                    if (str_contains($urlPart, ':')) {
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
        if (str_contains($url, static::$_URL_VAR_CHARACTER)) {
            throw new InvalidUrl([static::class, $url]);
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
        $pattern = "/(" . preg_quote(static::$_URL_VAR_CHARACTER) . ".*?[^\\/]*)/";
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
    public function reset(): static
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
