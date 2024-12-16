<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ModelInterface extends EndpointInterface, ClearableInterface, GetInterface, SetInterface
{
    /**
     * Get or Set the Model ID Key used by the Model
     */
    public static function modelIdKey($id = null): string;

    /**
     * Retrieve a Model by ID using a GET Request
     * @param $id
     * @return $this
     */
    public function retrieve($id = null): self;

    /**
     * Save the current Model
     * - Uses a POST if Model ID is not defined
     * - Uses a PUT request if Model ID is set
     * @return $this
     */
    public function save(): self;

    /**
     * Delete the current Model using a DELETE Request
     */
    public function delete(): self;
}
