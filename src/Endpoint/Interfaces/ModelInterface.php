<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface ModelInterface extends EndpointInterface, ClearableInterface, GetInterface, SetInterface
{
    /**
     * Get the Model Key (the unique identifying property)
     */
    public function getKeyProperty(): string;

    /**
     * Get the Model ID (the Models Key value)
     */
    public function getId(): string|int|null;

    /**
     * Retrieve a Model by ID using a GET Request
     * @param $id
     * @return $this
     */
    public function retrieve($id = null): static;

    /**
     * Save the current Model
     * - Uses a POST if Model ID is not defined
     * - Uses a PUT request if Model ID is set
     * @return $this
     */
    public function save(): static;

    /**
     * Delete the current Model using a DELETE Request
     */
    public function delete(): static;
}
