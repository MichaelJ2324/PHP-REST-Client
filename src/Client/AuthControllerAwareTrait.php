<?php

namespace MRussell\REST\Client;

use MRussell\REST\Auth\AuthControllerInterface;

trait AuthControllerAwareTrait
{
    /**
     * @var AuthControllerInterface
     */
    protected $Auth;

    /**
     * @inheritdoc
     */
    public function setAuth(AuthControllerInterface $Auth)
    {
        $this->Auth = $Auth;
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
        return $this->Auth;
    }
}
