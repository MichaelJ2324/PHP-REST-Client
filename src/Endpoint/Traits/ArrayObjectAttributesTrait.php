<?php

namespace MRussell\REST\Endpoint\Traits;

trait ArrayObjectAttributesTrait
{
    use ProtectedAttributesTrait;
    //Object Access
    /**
     * Get a data by key
     * @param string The key data to retrieve
     * @access public
     */
    public function &__get($key)
    {
        return $this->_attributes[$key];
    }

    /**
     * Assigns a value to the specified data
     * @param string $key - The data key to assign the value to
     * @param mixed $value - The value to set
     */
    public function __set($key, mixed $value)
    {
        $this->_attributes[$key] = $value;
    }

    /**
     * Whether or not an data exists by key
     * @param string $key - A data key to check for
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->_attributes[$key]);
    }

    /**
     * Unsets data by key
     * @param string $key - The key to unset
     */
    public function __unset($key)
    {
        unset($this->_attributes[$key]);
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
        if (is_null($offset)) {
            $this->_attributes[] = $value;
        } else {
            $this->_attributes[$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists
     * @param string $offset - An offset to check for
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->_attributes[$offset]);
    }

    /**
     * Unsets an offset
     * @param string $offset - The offset to unset
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset): void
    {
        if ($this->offsetExists($offset)) {
            unset($this->_attributes[$offset]);
        }
    }

    /**
     * Returns the value at specified offset
     * @param string $offset - The offset to retrieve
     * @return mixed
     * @abstracting ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->_attributes[$offset] : null;
    }

    /**
     * Return the array attributes
     * @implements ArrayableInterface
     */
    public function toArray(): array
    {
        return $this->_attributes;
    }
}
