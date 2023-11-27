<?php

namespace MRussell\REST\Tests\Stubs\Auth;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\Abstracts\AbstractAuthController;

class AuthController extends AbstractAuthController
{
    protected $token = '12345';

    public function configureRequest(Request $Request): Request
    {
        return $Request->withHeader('token', $this->token);
    }

    public function parseResponseToToken(string $action, Response $response)
    {
        return $response->getBody()->getContents();
    }

}
