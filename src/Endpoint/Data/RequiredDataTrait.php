<?php

namespace MRussell\REST\Endpoint\Data;

use MRussell\REST\Exception\Endpoint\InvalidData;

trait RequiredDataTrait
{
    /**
     * Validate Required Data for the Endpoint
     * @throws InvalidData
     */
    protected function verifyRequiredData(): bool
    {
        $errors = [
            ValidatedEndpointData::VALIDATION_MISSING => [],
            ValidatedEndpointData::VALIDATION_INVALID => [],
        ];
        $error = false;
        $requiredData = $this->getProperty(ValidatedEndpointData::DATA_PROPERTY_REQUIRED);
        if (!empty($requiredData)) {
            foreach ($requiredData as $property => $type) {
                if (!$this->offsetExists($property)) {
                    $errors[ValidatedEndpointData::VALIDATION_MISSING][] = $property;
                    $error = true;
                    continue;
                }

                if ($type !== null && gettype($this->get($property)) !== $type) {
                    $errors[ValidatedEndpointData::VALIDATION_INVALID][] = $property;
                    $error = true;
                }
            }
        }

        if ($error) {
            $errorMsg = '';
            if (!empty($errors[ValidatedEndpointData::VALIDATION_MISSING])) {
                $errorMsg .= "Missing [" . implode(",", $errors[ValidatedEndpointData::VALIDATION_MISSING]) . "] ";
            }

            if (!empty($errors[ValidatedEndpointData::VALIDATION_INVALID])) {
                $errorMsg .= "Invalid [" . implode(",", $errors[ValidatedEndpointData::VALIDATION_INVALID]) . "]";
            }

            throw new InvalidData(trim($errorMsg));
        }

        return !$error;
    }
}
