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
    protected Client $httpClient;

    protected HandlerStack $guzzleHandlerStack;

    protected string $server = '';

    protected string $apiURL = '';

    protected string $version;

    protected EndpointInterface $currentEndPoint;

    protected EndpointInterface $lastEndPoint;

    public function __construct()
    {
        $this->initHttpClient();
    }

    protected function initHttpClient(): void
    {
        $this->httpClient = new Client(['handler' => $this->getHandlerStack()]);
    }

    protected function initHttpHandlerStack(): void
    {
        $this->guzzleHandlerStack = HandlerStack::create();
    }

    public function getHttpClient(): Client
    {
        if (!isset($this->httpClient)) {
            $this->initHttpClient();
        }

        return $this->httpClient;
    }

    public function getHandlerStack(): HandlerStack
    {
        if (!isset($this->guzzleHandlerStack)) {
            $this->initHttpHandlerStack();
        }

        return $this->guzzleHandlerStack;
    }

    public function setHandlerStack(HandlerStack $stackHandler): static
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
     */
    protected function configureAuth(): void
    {
        $this->getHandlerStack()->remove('configureAuth');
        $this->getHandlerStack()->push(Middleware::mapRequest(function (Request $request): Request {
            $Auth = $this->getAuth();
            if ($Auth) {
                $EP = $this->current();
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
    public function setServer(string $server): static
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

    public function hasEndpoint(string $endpoint): bool
    {
        return $this->getEndpointProvider()->hasEndpoint($endpoint, $this->getVersion());
    }

    public function getEndpoint(string $endpoint): EndpointInterface
    {
        return $this->getEndpointProvider()->getEndpoint($endpoint, $this->getVersion());
    }

    /**
     * @inheritdoc
     */
    public function current(): EndpointInterface|null
    {
        return $this->currentEndPoint ?? null;
    }

    /**
     * @inheritdoc
     */
    public function last(): EndpointInterface|null
    {
        return $this->lastEndPoint ?? null;
    }

    /**
     * @inheritdoc
     */
    public function __call(string $name, $arguments): EndpointInterface
    {
        if (!isset($this->endpointProvider)) {
            throw new EndpointProviderMissing();
        }

        $this->setCurrentEndpoint($this->getEndpoint($name))
            ->current()
            ->setClient($this)
            ->setUrlArgs($arguments);
        return $this->currentEndPoint;
    }

    /**
     * Rotates current Endpoint to Last Endpoint, and sets Current Endpoint with passed in Endpoint
     * @return $this
     */
    protected function setCurrentEndpoint(EndpointInterface $Endpoint): static
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
