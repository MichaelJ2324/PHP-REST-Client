<?php

namespace MRussell\REST\Endpoint\Interfaces;

/**
 * Allow for getting/setting properties on the class
 */
interface PropertiesInterface
{
    /**
     * Set the properties that control the object
     * @return $this
     */
    public function setProperties(array $properties): static;

    /**
     * Set a specific property on an object
     * @return $this
     */
    public function setProperty(string $name, mixed $value): static;

    /**
     * Get the properties configured on the Data
     */
    public function getProperties(): array;

    /**
     * Get the properties configured on the Data
     */
    public function getProperty(string $name): mixed;
}
