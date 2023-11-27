<?php

namespace MRussell\REST\Client;

interface ClientAwareInterface
{
    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface;

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client);
}
