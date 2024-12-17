<?php

namespace MRussell\REST\Endpoint\Interfaces;

interface CollectionInterface extends EndpointInterface, ClearableInterface, GetInterface, ArrayableInterface
{
    /**
     * Retrieve the Endpoint Collection
     * @return $this
     */
    public function fetch(): static;

    /**
     * Set the Model Endpoint
     * @param mixed $model
     * @return $this
     */
    public function setModelEndpoint(ModelInterface $model): static;

    /**
     * Get a Model Endpoint based on Id
     * @param $id
     */
    public function get(string|int $key): ModelInterface|array|\ArrayAccess|null;

    /**
     * Get a Model Endpoint based on numerical index
     * @param $index
     * @return ModelInterface|null
     */
    public function at($index);

    /**
     * Set the collection of models
     * @param array $options = []
     * @return $this
     */
    public function set(array $models, array $options = []);
}
