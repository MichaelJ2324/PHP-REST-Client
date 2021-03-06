<?php

namespace MRussell\REST\Endpoint\Interfaces;


interface CollectionInterface extends EndpointInterface
{

    /**
     * Retrieve the Endpoint Collection
     * @return self
     */
    public function fetch();

    /**
     * Set the Model Endpoint
     * @param mixed $model
     * @return self
     */
    public function setModelEndpoint($model);

    /**
     * Get a Model Endpoint based on Id
     * @param $id
     * @return ModelInterface
     */
    public function get($id);
}