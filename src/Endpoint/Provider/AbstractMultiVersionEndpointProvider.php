<?php

namespace MRussell\REST\Endpoint\Provider;

abstract class AbstractMultiVersionEndpointProvider extends AbstractEndpointProvider
{
    protected function addEndpointRegistry(string $name, array $properties): void
    {
        if (isset($properties[self::ENDPOINT_NAME])) {
            $properties[self::ENDPOINT_NAME] = $name;
        }

        $this->registry[] = $properties;
    }

    protected function getEndpointDefinition(string $name, string $version = null): array
    {
        $eps = array_filter($this->registry, function ($definition) use ($name, $version): bool {
            if (($valid = $definition[self::ENDPOINT_NAME] === $name) && !empty($definition[self::ENDPOINT_VERSIONS])) {
                $ranges = $definition[self::ENDPOINT_VERSIONS];
                if (is_string($ranges)) {
                    $ranges = [$ranges];
                }

                $valid = $this->isInVersionRange($version, $ranges);
            }

            return $valid;
        });
        rsort($eps);
        return $eps[0];
    }
}
