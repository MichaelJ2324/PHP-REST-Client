<?php

namespace MRussell\REST\Endpoint\Interfaces;

/**
 * Allow for getting/setting properties on the class
 */
interface PropertiesInterface
{
    /**
     * Set the properties that control the object
     * @param array $properties
     * @return $this
     */
    public function setProperties(array $properties);

    /**
     * Set a specific property on an object
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setProperty(string $name, $value);

    /**
     * Get the properties configured on the Data
     * @return array
     */
    public function getProperties(): array;

    /**
     * Get the properties configured on the Data
     * @return mixed
     */
    public function getProperty(string $name);
}
