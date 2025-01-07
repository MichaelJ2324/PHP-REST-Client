<?php

namespace MRussell\REST\Client;

use MRussell\REST\Auth\AuthControllerInterface;

interface AuthControllerAwareInterface
{
    /**
     * Set the Auth Controller that handles Auth for the API
     * @return $this
     */
    public function setAuth(AuthControllerInterface $Auth): static;


    public function getAuth(): AuthControllerInterface;
}
