<?php

namespace MRussell\REST\Client;

use MRussell\REST\Auth\AuthControllerInterface;

interface AuthControllerAwareInterface
{
    /**
     * Set the Auth Controller that handles Auth for the API
     * @param AuthControllerInterface $Auth
     * @return $this
     */
    public function setAuth(AuthControllerInterface $Auth);

    /**
     *
     * @return AuthControllerInterface
     */
    public function getAuth();
}
