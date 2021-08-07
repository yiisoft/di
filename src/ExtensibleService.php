<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Definition\DefinitionInterface;
use Yiisoft\Factory\DependencyResolverInterface;

/**
 * A wrapper for a service definition that allows registering extensions.
 * An extension is a callable that returns a modified service object:
 *
 * ```php
 * static function (ContainerInterface $container, $service) {
 *     return $service->withAnotherOption(42);
 * }
 * ```
 */
final class ExtensibleService implements DefinitionInterface
{
    private DefinitionInterface $definition;
    private array $extensions = [];

    /**
     * @param DefinitionInterface $definition Definition to allow registering extensions for.
     */
    public function __construct(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Add an extension.
     *
     * An extension is a callable that returns a modified service object:
     *
     * ```php
     * static function (ContainerInterface $container, $service) {
     *     return $service->withAnotherOption(42);
     * }
     * ```
     *
     * @param callable $closure An extension to register.
     */
    public function addExtension(callable $closure): void
    {
        $this->extensions[] = $closure;
    }

    public function resolve(DependencyResolverInterface $container)
    {
        $service = $this->definition->resolve($container);
        $containerInterface = $container->get(ContainerInterface::class);
        foreach ($this->extensions as $extension) {
            $service = $extension($containerInterface, $service);
        }

        return $service;
    }
}
