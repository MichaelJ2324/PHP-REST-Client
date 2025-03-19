<?php

namespace MRussell\REST\Endpoint\Data;

use MRussell\REST\Endpoint\Interfaces\ValidatedInterface;
use MRussell\REST\Exception\Endpoint\InvalidData;

class ValidatedEndpointData extends EndpointData implements ValidatedInterface
{
    use RequiredDataTrait;
    public const DATA_PROPERTY_REQUIRED = 'required';

    public const DATA_PROPERTY_AUTO_VALIDATE = 'auto_validate';

    public const VALIDATION_MISSING = 'missing';

    public const VALIDATION_INVALID = 'invalid';

    protected static array $_DEFAULT_PROPERTIES = [
        self::DATA_PROPERTY_REQUIRED => [],
        self::DATA_PROPERTY_AUTO_VALIDATE => false,
    ];

    public function setProperties(array $properties): static
    {
        if (!isset($properties[self::DATA_PROPERTY_REQUIRED])) {
            $properties[self::DATA_PROPERTY_REQUIRED] = [];
        }

        if (!isset($properties[self::DATA_PROPERTY_AUTO_VALIDATE])) {
            $properties[self::DATA_PROPERTY_AUTO_VALIDATE] = false;
        }

        $properties[self::DATA_PROPERTY_AUTO_VALIDATE] = (bool) $properties[self::DATA_PROPERTY_AUTO_VALIDATE];
        return parent::setProperties($properties);
    }

    /**
     * Verify data requirements when converting to Array
     * @throws InvalidData
     */
    public function toArray(bool $validate = null): array
    {
        $validate = is_null($validate) ? $this->getProperty(self::DATA_PROPERTY_AUTO_VALIDATE) : $validate;
        if ((bool) $validate && !$this->validate()) {
            throw new InvalidData("Validation failed");
        }

        return $this->_attributes;
    }

    public function validate(): bool
    {
        return $this->verifyRequiredData();
    }
}
