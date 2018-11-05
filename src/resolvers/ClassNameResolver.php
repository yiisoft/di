<?php


namespace yii\di\resolvers;

use yii\di\contracts\DependencyInterface;
use yii\di\contracts\DependencyResolverInterface;
use yii\di\dependencies\ClassDependency;
use yii\di\dependencies\InvalidDependency;
use yii\di\dependencies\ValueDependency;
use yii\di\exceptions\NotInstantiableException;

/**
 * Class ClassNameResolver
 * This implementation resolves dependencies by using class type hints.
 * Note that service names need not match the parameter names, parameter names are ignored
 */
class ClassNameResolver implements DependencyResolverInterface
{

    /**
     * @inheritdoc
     */
    public function resolveConstructor(string $class): array
    {
        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->isInstantiable()) {
            throw new NotInstantiableException($class);
        }
        $constructor = $reflectionClass->getConstructor();
        return isset($constructor) ? $this->resolveFunction($constructor) : [];
    }

    private function resolveFunction(\ReflectionFunctionAbstract $reflectionFunction): array
    {
        $result = [];
        foreach ($reflectionFunction->getParameters() as $parameter) {
            $result[] = $this->resolveParameter($parameter);
        }
        return $result;
    }

    private function resolveParameter(\ReflectionParameter $parameter): DependencyInterface
    {
        $type = $parameter->getType();
        $hasDefault = $parameter->isOptional() || $parameter->isDefaultValueAvailable() || (isset($type) && $type->allowsNull());

        if (!$hasDefault && !isset($type)) {
            return new InvalidDependency();
        }

        // Our parameter has a class type hint
        if (isset($type) && !$type->isBuiltin()) {
            return new ClassDependency($type->getName(), $type->allowsNull());
        }

        // Our parameter does not have a class type hint and either has a default value or is nullable.
        return new ValueDependency($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
    }

    /**
     * @inheritdoc
     */
    public function resolveCallable(callable $callable): array
    {
        return $this->resolveFunction(new \ReflectionFunction(\Closure::fromCallable($callable)));
    }
}
