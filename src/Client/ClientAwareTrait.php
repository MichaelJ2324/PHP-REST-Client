<?php

namespace MRussell\REST\Client;

trait ClientAwareTrait
{
    protected ClientInterface $_client;

    /**
     * @return $this
     */
    public function setClient(ClientInterface $_client): static
    {
        $this->_client = $_client;
        return $this;
    }

    public function getClient(): ClientInterface
    {
        return $this->_client;
    }
}
