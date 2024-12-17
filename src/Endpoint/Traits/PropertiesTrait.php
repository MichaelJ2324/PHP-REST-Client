<?php

namespace MRussell\REST\Endpoint\Traits;

trait PropertiesTrait
{
    /**
     * @var array
     */
    protected $_properties = [];

    /**
     * Get the current Data Properties
     * @implements PropertiesInterface
     */
    public function getProperties(): array
    {
        return $this->_properties;
    }

    /**
     * Set the properties array
     * @return $this
     * @implements PropertiesInterface
     */
    public function setProperties(array $properties)
    {
        $this->_properties = $properties;
        return $this;
    }

    /**
     * Set a property in properties array
     * @param $value
     * @return $this
     * @implements PropertiesInterface
     */
    public function setProperty(string $name, $value)
    {
        $properties = $this->getProperties();
        $properties[$name] = $value;
        return $this->setProperties($properties);
    }

    /**
     * Get a specific property from properties array
     * @return mixed
     * @implements PropertiesInterface
     */
    public function getProperty(string $name)
    {
        return $this->_properties[$name] ?? null;
    }
}
