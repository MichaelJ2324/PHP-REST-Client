<?php

namespace MRussell\REST\Endpoint\Data;

use MRussell\REST\Endpoint\Traits\ArrayObjectAttributesTrait;
use MRussell\REST\Endpoint\Traits\ClearAttributesTrait;
use MRussell\REST\Endpoint\Traits\GetAttributesTrait;
use MRussell\REST\Endpoint\Traits\PropertiesTrait;
use MRussell\REST\Endpoint\Traits\SetAttributesTrait;

abstract class AbstractEndpointData implements DataInterface
{
    use GetAttributesTrait;
    use ClearAttributesTrait {
        clear as private clearAttributes;
    }
    use ArrayObjectAttributesTrait;
    use SetAttributesTrait {
        set as private setAttributes;
    }
    use PropertiesTrait {
        setProperties as rawSetProperties;
    }

    /**
     * A way to determine between Empty Array and Null
     */
    protected bool $isNull = true;

    public const DATA_PROPERTY_DEFAULTS = 'defaults';

    public const DATA_PROPERTY_NULLABLE = 'nullable';

    protected static array $_DEFAULT_PROPERTIES = [
        self::DATA_PROPERTY_DEFAULTS => [],
        self::DATA_PROPERTY_NULLABLE => true,
    ];

    //Overloads
    public function __construct(array $data = null, array $properties = [])
    {
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }

        $this->configureDefaultData();
        if (!empty($data)) {
            $this->set($data);
        }
    }

    public function __set($key, $value)
    {
        $this->isNull = false;
        $this->_attributes[$key] = $value;
    }

    //Array Access
    /**
     * Assigns a value to the specified offset
     * @param string $offset - The offset to assign the value to
     * @param mixed $value - The value to set
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->isNull = false;
        if (is_null($offset)) {
            $this->_attributes[] = $value;
        } else {
            $this->_attributes[$offset] = $value;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value = null): static
    {
        if ((is_array($key) && !empty($key)) || !is_array($key)) {
            $this->isNull = false;
        }

        return $this->setAttributes($key, $value);
    }

    /**
     * Set properties for data
     */
    public function setProperties(array $properties): static
    {
        if (!isset($properties[self::DATA_PROPERTY_DEFAULTS])) {
            $properties[self::DATA_PROPERTY_DEFAULTS] = [];
        }

        if (!isset($properties[self::DATA_PROPERTY_NULLABLE])) {
            $properties[self::DATA_PROPERTY_NULLABLE] = true;
        }

        $properties[self::DATA_PROPERTY_NULLABLE] = (bool) $properties[self::DATA_PROPERTY_NULLABLE];

        return $this->rawSetProperties($properties);
    }

    /**
     * Set Data back to Defaults and clear out data
     * @implements ResettableInterface
     */
    public function reset(): static
    {
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        return $this->clear()->configureDefaultData();
    }

    /**
     * Set data to null
     * @return $this
     */
    public function clear(): static
    {
        $this->clearAttributes();
        $this->isNull = true;
        return $this;
    }

    public function isNull(): bool
    {
        return $this->isNullable() && $this->isNull && empty($this->_attributes);
    }

    public function isNullable(): bool
    {
        $default = true;
        if (isset(static::$_DEFAULT_PROPERTIES[self::DATA_PROPERTY_NULLABLE]) && is_bool(static::$_DEFAULT_PROPERTIES[self::DATA_PROPERTY_NULLABLE])) {
            $default = static::$_DEFAULT_PROPERTIES[self::DATA_PROPERTY_NULLABLE];
        }

        $nullable = $this->getProperty(self::DATA_PROPERTY_NULLABLE);
        return $nullable !== null ? $nullable : $default;
    }

    /**
     * Configures Data with defaults based on properties array
     * @return $this
     */
    protected function configureDefaultData(): self
    {
        if (isset($this->_properties[self::DATA_PROPERTY_DEFAULTS])
            && is_array($this->_properties[self::DATA_PROPERTY_DEFAULTS])
            && !empty($this->_properties[self::DATA_PROPERTY_DEFAULTS])) {
            $this->set($this->_properties[self::DATA_PROPERTY_DEFAULTS]);
        }

        return $this;
    }

    public function toArray(bool $validate = null): array
    {
        return $this->_attributes;
    }
}
