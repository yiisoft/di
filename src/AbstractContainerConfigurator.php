<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

abstract class AbstractContainerConfigurator
{
    protected function set(string $id, $definition): void
    {
        throw new \RuntimeException("Method 'set' does not exist.");
    }

    protected function setMultiple(array $config): void
    {
        throw new \RuntimeException("Method 'setMultiple' does not exist.");
    }

    protected function delegateLookup(ContainerInterface $container): void
    {
        throw new \RuntimeException("Method 'delegateLookup' does not exist.");
    }

    protected function hasDefinition($id): bool
    {
        throw new \RuntimeException("Method 'hasDefinition' does not exist.");
    }
}
