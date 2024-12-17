<?php

namespace MRussell\REST\Endpoint\Traits;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait JsonHandlerTrait
{
    protected $respContent;

    protected function configureJsonRequest(Request $request): Request
    {
        return $request->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return JSON Decoded response body
     * @param $associative
     * @return mixed
     */
    public function getResponseContent(Response $response, bool $associative = true)
    {
        if (!$this->respContent) {
            $this->respContent = $response->getBody()->getContents();
            $response->getBody()->rewind();
        }

        $body = null;
        try {
            $body = json_decode($this->respContent, $associative);
            // @codeCoverageIgnoreStart
        } catch (\Exception) {
        }

        // @codeCoverageIgnoreEnd
        return $body;
    }
}
