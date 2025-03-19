<?php

namespace MRussell\REST\Tests\Stubs\Auth;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRussell\REST\Auth\Abstracts\AbstractAuthController;

class AuthController extends AbstractAuthController
{
    protected mixed $token = '12345';

    public function configureRequest(Request $Request): Request
    {
        return $Request->withHeader('token', $this->token);
    }

    protected function parseResponseToToken(string $action, Response $response): string
    {
        return $response->getBody()->getContents();
    }

}
