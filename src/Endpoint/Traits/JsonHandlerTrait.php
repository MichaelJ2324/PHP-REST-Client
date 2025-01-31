<?php

namespace MRussell\REST\Endpoint\Traits;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait JsonHandlerTrait
{
    protected string|null $_respContent;

    protected function configureJsonRequest(Request $request): Request
    {
        return $request->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return JSON Decoded response body
     */
    public function getResponseContent(Response $response, bool $associative = true): mixed
    {
        if (!$this->_respContent) {
            $this->_respContent = $response->getBody()->getContents();
            $response->getBody()->rewind();
        }

        $body = null;
        try {
            $body = json_decode($this->_respContent, $associative);
            // @codeCoverageIgnoreStart
        } catch (\Exception) {
        }

        // @codeCoverageIgnoreEnd
        return $body;
    }
}
