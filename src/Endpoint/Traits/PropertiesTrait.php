<?php

namespace MRussell\REST\Endpoint\Traits;

trait PropertiesTrait
{
    protected array $_properties = [];

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
    public function setProperties(array $properties): static
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
    public function setProperty(string $name, $value): static
    {
        $properties = $this->getProperties();
        $properties[$name] = $value;
        return $this->setProperties($properties);
    }

    /**
     * Get a specific property from properties array
     * @implements PropertiesInterface
     */
    public function getProperty(string $name): mixed
    {
        return $this->_properties[$name] ?? null;
    }
}
