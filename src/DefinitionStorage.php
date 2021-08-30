<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use Yiisoft\Definitions\Exception\CircularReferenceException;

/**
 * Stores service definitions and checks if a definition could be instantiated.
 *
 * @internal
 */
final class DefinitionStorage
{
    private array $definitions;
    private array $lastBuilding = [];
    /** @psalm-suppress  PropertyNotSetInConstructor */
    private ContainerInterface $delegateContainer;

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    public function setDelegateContainer(ContainerInterface $delegateContainer): void
    {
        $this->delegateContainer = $delegateContainer;
    }

    /**
     * Checks if there is a definition with ID specified and that it can be created.
     *
     * @param string $id class name, interface name or alias name
     *
     * @throws CircularReferenceException
     */
    public function has(string $id): bool
    {
        $this->lastBuilding = [];
        return $this->isResolvable($id, []);
    }

    public function getLastBuilding(): array
    {
        return $this->lastBuilding;
    }

    /**
     * Get a definition with a given ID.
     *
     * @return mixed|object Definition with a given ID.
     */
    public function get(string $id)
    {
        if (!isset($this->definitions[$id])) {
            throw new \RuntimeException("Service $id doesn't exist in DefinitionStorage.");
        }
        return $this->definitions[$id];
    }

    /**
     * Set a definition.
     *
     * @param string $id ID to set definition for.
     * @param mixed|object $definition Definition to set.
     */
    public function set(string $id, $definition): void
    {
        $this->definitions[$id] = $definition;
    }

    private function isResolvable(string $id, array $building): bool
    {
        if (isset($this->definitions[$id])) {
            return true;
        }

        if (!class_exists($id)) {
            $this->lastBuilding += array_merge($building, [$id => 1]);
            return false;
        }

        if (isset($building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(', ', array_keys($building))
            ));
        }

        try {
            $reflectionClass = new ReflectionClass($id);
        } catch (ReflectionException $e) {
            $this->lastBuilding += array_merge($building, [$id => 1]);
            return false;
        }

        if (!$reflectionClass->isInstantiable()) {
            $this->lastBuilding = array_merge($this->lastBuilding, [$id => 1]);
            return false;
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            $this->definitions[$id] = $id;
            return true;
        }

        $isResolvable = true;
        $building[$id] = 1;

        try {
            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($parameter->isVariadic() || $parameter->isOptional()) {
                    break;
                }

                /**
                 * @var ReflectionNamedType|ReflectionUnionType|null $type
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 * @psalm-suppress UndefinedClass
                 */
                if ($type === null || !$type instanceof ReflectionUnionType && $type->isBuiltin()) {
                    $isResolvable = false;
                    break;
                }

                // PHP 8 union type is used as type hint
                /** @psalm-suppress UndefinedClass, TypeDoesNotContainType */
                if ($type instanceof ReflectionUnionType) {
                    $isUnionTypeResolvable = false;
                    $unionTypes = [];
                    /** @var ReflectionNamedType $unionType */
                    foreach ($type->getTypes() as $unionType) {
                        if (!$unionType->isBuiltin()) {
                            $typeName = $unionType->getName();
                            if ($typeName === 'self') {
                                continue;
                            }
                            $unionTypes[] = $typeName;
                            if ($this->isResolvable($typeName, $building)) {
                                $isUnionTypeResolvable = true;
                                break;
                            }
                        }
                    }


                    if (!$isUnionTypeResolvable) {
                        foreach ($unionTypes as $typeName) {
                            if ($this->delegateContainer->has($typeName)) {
                                $isUnionTypeResolvable = true;
                                break;
                            }
                        }

                        $isResolvable = $isUnionTypeResolvable;
                        if (!$isResolvable) {
                            break;
                        }
                    }
                    continue;
                }

                /** @var ReflectionNamedType|null $type */
                // Our parameter has a class type hint
                if ($type !== null && !$type->isBuiltin()) {
                    $typeName = $type->getName();

                    if ($typeName === 'self') {
                        throw new CircularReferenceException(sprintf(
                            'Circular reference to "%s" detected while building: %s.',
                            $id,
                            implode(', ', array_keys($building))
                        ));
                    }

                    /** @psalm-suppress RedundantPropertyInitializationCheck */
                    if (!($this->isResolvable($typeName, $building) || (isset($this->delegateContainer) ? $this->delegateContainer->has($typeName) : false))) {
                        $isResolvable = false;
                        break;
                    }
                }
            }
        } finally {
            $this->lastBuilding += $building;
            unset($building[$id]);
        }

        if ($isResolvable) {
            $this->definitions[$id] = $id;
        }

        return $isResolvable;
    }
}
