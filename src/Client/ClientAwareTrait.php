<?php

namespace MRussell\REST\Client;

trait ClientAwareTrait
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
