<?php


namespace yii\di\contracts;

use yii\di\exceptions\NotInstantiableException;

/**
 * Interface DependencyResolverInterface
 *
 * @package yii\di\contracts
 */
interface DependencyResolverInterface
{
    /**
     *
     * @return DefinitionInterface[] An array of direct dependencies of $class.
     * @throws NotInstantiableException If the class is not instantiable this MUST throw a NotInstantiableException
     */
    public function resolveConstructor(string $class): array;

    /**
     * @param callable $callable
     * @return DefinitionInterface[] An array of direct dependencies of the callable.
     */
    public function resolveCallable(callable $callable): array;
}
