<?php

namespace MRussell\REST\Endpoint\Traits;

trait ParseResponseBodyToArrayTrait
{
    /**
     * @param $body
     */
    protected function parseResponseBodyToArray($body, string $prop = ""): array
    {
        if (empty($prop)) {
            if (is_object($body)) {
                $body = json_decode(json_encode($body), true);
            }

            return is_array($body) ? $body : [];
        } elseif (is_object($body) && isset($body->$prop)) {
            return $this->parseResponseBodyToArray($body->$prop);
        } elseif (is_array($body) && isset($body[$prop])) {
            return $this->parseResponseBodyToArray($body[$prop]);
        }

        return [];
    }
}
