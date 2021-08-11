<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use Yiisoft\Factory\Exception\CircularReferenceException;

final class DefinitionStorage
{
    private array $definitions = [];
    private array $building = [];
    private ContainerInterface $delegateContainer;

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    public function setDelegateContainer(ContainerInterface $delegateContainer)
    {
        $this->delegateContainer = $delegateContainer;
    }

    /**
     * @param string $id class name, interface name or alias name
     *
     * @throws CircularReferenceException
     */
    public function hasDefinition(string $id): bool
    {
        if (isset($this->definitions[$id])) {
            return true;
        }

        if (!class_exists($id)) {
            return false;
        }

        if (isset($this->building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(', ', array_keys($this->building))
            ));
        }

        try {
            $reflectionClass = new ReflectionClass($id);
        } catch (ReflectionException $e) {
            return false;
        }

        if (!$reflectionClass->isInstantiable()) {
            return false;
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            $this->definitions[$id] = $id;
            return true;
        }

        $isResolvable = true;
        $this->building[$id] = 1;

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
                /** @var ReflectionNamedType $unionType */
                foreach ($type->getTypes() as $unionType) {
                    if (!$unionType->isBuiltin()) {
                        $typeName = $unionType->getName();
                        if ($typeName === 'self') {
                            continue;
                        }
                        if ($this->hasDefinition($typeName)) {
                            $isUnionTypeResolvable = true;
                            break;
                        }
                    }
                }

                $isResolvable = $isUnionTypeResolvable;
                if (!$isResolvable) {
                    break;
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
                        implode(', ', array_keys($this->building))
                    ));
                }

                if (!($this->hasDefinition($typeName) || (isset($this->delegateContainer) ? $this->delegateContainer->has($typeName): false))) {
                    $isResolvable = false;
                    break;
                }
            }
        }

        if ($isResolvable) {
            $this->definitions[$id] = $id;
        }

        unset($this->building[$id]);

        return $isResolvable;
    }

    /**
    * @return mixed|object
    */
    public function getDefinition(string $id)
    {
        return $this->definitions[$id];
    }

    /**
     * @param mixed|object $definition
     */
    public function setDefinition(string $id, $definition): void
    {
        $this->definitions[$id] = $definition;
    }
}
