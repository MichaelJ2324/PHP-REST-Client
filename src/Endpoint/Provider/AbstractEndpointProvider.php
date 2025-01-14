<?php

namespace MRussell\REST\Endpoint\Provider;

use MRussell\REST\Endpoint\Interfaces\EndpointInterface;
use MRussell\REST\Exception\Endpoint\InvalidRegistration;
use MRussell\REST\Exception\Endpoint\UnknownEndpoint;

abstract class AbstractEndpointProvider implements EndpointProviderInterface
{
    public const ENDPOINT_CLASS = 'class';

    public const ENDPOINT_PROPERTIES = 'properties';

    public const ENDPOINT_VERSIONS = 'versions';

    public const ENDPOINT_NAME = 'name';

    protected array $registry = [];

    /**
     * @inheritdoc
     * @throws InvalidRegistration
     */
    public function registerEndpoint($name, $className, array $properties = []): static
    {
        try {
            $implements = class_implements($className);
            if (is_array($implements) && isset($implements[EndpointInterface::class])) {
                if (isset($properties[self::ENDPOINT_VERSIONS])) {
                    $versions = $properties[self::ENDPOINT_VERSIONS];
                }

                $this->addEndpointRegistry($name, [
                    self::ENDPOINT_NAME => $name,
                    self::ENDPOINT_CLASS => $className,
                    self::ENDPOINT_PROPERTIES => $properties,
                    self::ENDPOINT_VERSIONS => $versions ?? [],
                ]);
                return $this;
            }
        } catch (\Exception) {
            //Class Implements failed to Load Class completely
        }

        throw new InvalidRegistration([$className]);
    }

    protected function addEndpointRegistry(string $name, array $properties): void
    {
        if (isset($properties[self::ENDPOINT_NAME])) {
            $properties[self::ENDPOINT_NAME] = $name;
        }

        $this->registry[$name] = $properties;
    }

    /**
     * @inheritdoc
     */
    public function hasEndpoint(string $name, string $version = null): bool
    {
        $definition = $this->getEndpointDefinition($name, $version);
        return !empty($definition);
    }

    protected function getEndpointDefinition(string $name, string $version = null): array
    {
        $definition = $this->registry[$name] ?? [];
        if (!empty($definition) && $version !== null && !empty($definition[self::ENDPOINT_VERSIONS])) {
            $ranges = $this->registry[$name][self::ENDPOINT_VERSIONS];
            if (is_string($ranges)) {
                $ranges = [$ranges];
            }

            if (!$this->isInVersionRange($version, $ranges)) {
                $definition = [];
            }
        }

        return $definition;
    }

    /**
     * @inheritdoc
     * @throws UnknownEndpoint
     */
    public function getEndpoint(string $name, string $version = null): EndpointInterface
    {
        if ($this->hasEndpoint($name, $version)) {
            return $this->buildEndpoint($name, $version);
        } else {
            throw new UnknownEndpoint($name);
        }
    }

    protected function buildEndpoint(string $name, string $version = null): EndpointInterface
    {
        $endPointDef = $this->getEndpointDefinition($name, $version);
        $Class = $endPointDef[self::ENDPOINT_CLASS];
        $properties = $endPointDef[self::ENDPOINT_PROPERTIES] ?? [];
        $Endpoint = new $Class();
        if (!empty($properties)) {
            foreach ($properties as $prop => $value) {
                $Endpoint->setProperty($prop, $value);
            }
        }

        return $Endpoint;
    }

    protected function isInVersionRange(string $version, array $ranges): bool
    {
        $is = false;
        foreach ($ranges as $range) {
            $internalComp = true;
            if (is_array($range)) {
                foreach ($range as $compare => $v) {
                    if (!version_compare($version, $v, $compare)) {
                        $internalComp = false;
                        break;
                    }
                }
            } else {
                $internalComp = $range == $version;
            }

            if ($internalComp) {
                $is = true;
                break;
            }
        }

        return $is;
    }
}
