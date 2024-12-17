<?php

namespace MRussell\REST\Client;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Client\EndpointProviderMissing;
use GuzzleHttp\Psr7\Request;

/**
 * A Generic Abstract Client
 * @package MRussell\REST\Client\Abstracts\AbstractClient
 */
abstract class AbstractClient implements ClientInterface, AuthControllerAwareInterface, EndpointProviderAwareInterface
{
    use AuthControllerAwareTrait;
    use EndpointProviderAwareTrait;
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var HandlerStack
     */
    protected $guzzleHandlerStack;

    /**
     * @var string
     */
    protected $server = '';

    /**
     * @var string
     */
    protected $apiURL = '';

    /**
     * @var string
     */
    protected $version;

    /**
     * @var EndpointInterface
     */
    protected $currentEndPoint;

    /**
     * @var EndpointInterface
     */
    protected $lastEndPoint;

    /**
     * @var mixed
     */
    protected $error;

    public function __construct()
    {
        $this->initHttpClient();
    }

    /**
     * @return void
     */
    protected function initHttpClient()
    {
        $this->httpClient = new Client(['handler' => $this->getHandlerStack()]);
    }

    /**
     * @return void
     */
    protected function initHttpHandlerStack()
    {
        $this->guzzleHandlerStack = HandlerStack::create();
    }

    public function getHttpClient(): Client
    {
        if ($this->httpClient == null) {
            $this->initHttpClient();
        }

        return $this->httpClient;
    }

    public function getHandlerStack(): HandlerStack
    {
        if (!$this->guzzleHandlerStack) {
            $this->initHttpHandlerStack();
        }

        return $this->guzzleHandlerStack;
    }

    /**
     * @return $this
     */
    public function setHandlerStack(HandlerStack $stackHandler)
    {
        $this->guzzleHandlerStack = $stackHandler;
        $this->initHttpClient();
        if (isset($this->auth)) {
            $this->configureAuth();
        }

        return $this;
    }

    /**
     * Configure the HandlerStack to have the Auth middleware
     * @return void
     */
    protected function configureAuth()
    {
        $api = $this;
        $this->getHandlerStack()->remove('configureAuth');
        $this->getHandlerStack()->push(Middleware::mapRequest(function (Request $request) use ($api): Request {
            $Auth = $api->getAuth();
            if ($Auth) {
                $EP = $api->current();
                if ($EP && $EP->useAuth()) {
                    return $Auth->configureRequest($request);
                }
            }

            return $request;
        }), 'configureAuth');
    }

    /**
     * @inheritdoc
     */
    public function setServer($server): static
    {
        $this->server = $server;
        $this->setAPIUrl();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getServer(): string
    {
        return $this->server ?? "";
    }

    /**
     * @inheritdoc
     */
    protected function setAPIUrl(): static
    {
        $this->apiURL = $this->server;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAPIUrl(): string
    {
        return $this->apiURL ?? "";
    }

    /**
     * @inheritdoc
     */
    public function setVersion(string $version): static
    {
        $this->version = $version;
        $this->setAPIUrl();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getVersion(): string
    {
        return $this->version ?? "";
    }

    /**
     * @inheritdoc
     */
    public function current(): EndpointInterface
    {
        return $this->currentEndPoint;
    }

    /**
     * @inheritdoc
     */
    public function last(): EndpointInterface
    {
        return $this->lastEndPoint;
    }

    /**
     * @inheritdoc
     */
    public function __call(string $name, $arguments)
    {
        if (!isset($this->endpointProvider)){
            throw new EndpointProviderMissing();
        }

        $provider = $this->getEndpointProvider();
        $this->setCurrentEndpoint($provider->getEndpoint($name, $this->version))
            ->current()
            ->setClient($this)
            ->setUrlArgs($arguments);
        return $this->currentEndPoint;
    }

    /**
     * Rotates current Endpoint to Last Endpoint, and sets Current Endpoint with passed in Endpoint
     * @return $this
     */
    protected function setCurrentEndpoint(EndpointInterface $Endpoint)
    {
        if (isset($this->currentEndPoint)) {
            unset($this->lastEndPoint);
            $this->lastEndPoint = $this->currentEndPoint;
            unset($this->currentEndPoint);
        }

        $this->currentEndPoint = $Endpoint;
        return $this;
    }
}
