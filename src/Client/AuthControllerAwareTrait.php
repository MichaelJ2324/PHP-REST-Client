<?php

namespace MRussell\REST\Client;

use MRussell\REST\Auth\AuthControllerInterface;

trait AuthControllerAwareTrait
{
    protected AuthControllerInterface $auth;

    /**
     * @inheritdoc
     */
    public function setAuth(AuthControllerInterface $auth): static
    {
        $this->auth = $auth;
        $this->configureAuth();
        return $this;
    }

    /**
     * @return void
     */
    abstract protected function configureAuth();

    /**
     * @implements AuthControllerAwareInterface
     */
    public function getAuth(): AuthControllerInterface
    {
        return $this->auth;
    }
}
