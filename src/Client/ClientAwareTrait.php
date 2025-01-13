<?php

namespace MRussell\REST\Client;

trait ClientAwareTrait
{
    protected ClientInterface $client;

    /**
     * @return $this
     */
    public function setClient(ClientInterface $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }
}
