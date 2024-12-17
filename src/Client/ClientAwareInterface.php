<?php

namespace MRussell\REST\Client;

interface ClientAwareInterface
{
    public function getClient(): ClientInterface;

    /**
     * @return $this
     */
    public function setClient(ClientInterface $client);
}
