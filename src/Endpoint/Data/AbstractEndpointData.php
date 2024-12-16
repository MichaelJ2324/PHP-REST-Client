<?php

namespace MRussell\REST\Endpoint\Data;

use MRussell\REST\Endpoint\Traits\ArrayObjectAttributesTrait;
use MRussell\REST\Endpoint\Traits\ClearAttributesTrait;
use MRussell\REST\Endpoint\Traits\GetAttributesTrait;
use MRussell\REST\Endpoint\Traits\PropertiesTrait;
use MRussell\REST\Endpoint\Traits\SetAttributesTrait;
use MRussell\REST\Exception\Endpoint\InvalidData;

abstract class AbstractEndpointData implements DataInterface
{
    use GetAttributesTrait;
    use ClearAttributesTrait;
    use ArrayObjectAttributesTrait;
    use SetAttributesTrait {
        set as private setAttributes;
    }
    use PropertiesTrait {
        setProperties as rawSetProperties;
    }

    /**
     * A way to determine between Empty Array and Null
     * @var bool
     */
    protected $isNull = true;

    public const DATA_PROPERTY_REQUIRED = 'required';

    public const DATA_PROPERTY_DEFAULTS = 'defaults';

    protected static $_DEFAULT_PROPERTIES = [self::DATA_PROPERTY_REQUIRED => [], self::DATA_PROPERTY_DEFAULTS => []];

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
    public function offsetSet($offset, $value): void
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
    public function set($key, $value = null)
    {
        if ((is_array($key) && !empty($key)) || !is_array($key)) {
            $this->isNull = false;
        }

        return $this->setAttributes($key, $value);
    }

    /**
     * Set properties for data
     */
    public function setProperties(array $properties): void
    {
        if (!isset($properties[self::DATA_PROPERTY_REQUIRED])) {
            $properties[self::DATA_PROPERTY_REQUIRED] = [];
        }

        if (!isset($properties[self::DATA_PROPERTY_DEFAULTS])) {
            $properties[self::DATA_PROPERTY_DEFAULTS] = [];
        }

        $this->rawSetProperties($properties);
    }

    /**
     * Set Data back to Defaults and clear out data
     * @return AbstractEndpointData
     * @implements ResettableInterface
     */
    public function reset(): DataInterface
    {
        $this->setProperties(static::$_DEFAULT_PROPERTIES);
        $this->null();
        return $this->configureDefaultData();
    }

    /**
     * Set data to null
     * @return $this
     */
    public function null()
    {
        $this->clear();
        $this->isNull = true;
        return $this;
    }

    public function isNull(): bool
    {
        return $this->isNull && empty($this->_attributes);
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

    /**
     * Verify data requirements when converting to Array
     * @throws InvalidData
     */
    public function toArray(bool $verify = false): array
    {
        if ($verify) {
            $this->verifyRequiredData();
        }

        return $this->_attributes;
    }

    /**
     * Validate Required Data for the Endpoint
     * @throws InvalidData
     */
    protected function verifyRequiredData(): bool
    {
        $errors = [
            'missing' => [],
            'invalid' => [],
        ];
        $error = false;
        if (!empty($this->_properties[self::DATA_PROPERTY_REQUIRED])) {
            foreach ($this->_properties[self::DATA_PROPERTY_REQUIRED] as $property => $type) {
                if (!isset($this->_attributes[$property])) {
                    $errors['missing'][] = $property;
                    $error = true;
                    continue;
                }

                if ($type !== null && gettype($this->_attributes[$property]) !== $type) {
                    $errors['invalid'][] = $property;
                    $error = true;
                }
            }
        }

        if ($error) {
            $errorMsg = '';
            if (!empty($errors['missing'])) {
                $errorMsg .= "Missing [" . implode(",", $errors['missing']) . "] ";
            }

            if (!empty($errors['invalid'])) {
                $errorMsg .= "Invalid [" . implode(",", $errors['invalid']) . "]";
            }

            throw new InvalidData(trim($errorMsg));
        }

        return $error;
    }
}
