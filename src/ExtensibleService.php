<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Definition\DefinitionInterface;
use Yiisoft\Factory\Definition\Normalizer;
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
    /** @psalm-var  array<string,mixed> */
    private $definition;
    private array $extensions = [];

    /** @param mixed $definition */
    public function __construct($definition)
    {
        $this->definition = $definition;
    }

    public function addExtension(\Closure $closure): void
    {
        $this->extensions[] = $closure;
    }

    public function resolve(DependencyResolverInterface $container)
    {
        $service = (Normalizer::normalize($this->definition))->resolve($container);
        $containerInterface = $container->get(ContainerInterface::class);
        foreach ($this->extensions as $extension) {
            $service = $extension($containerInterface, $service);
        }

        return $service;
    }
}
