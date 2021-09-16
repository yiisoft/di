<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Definitions\Infrastructure\Normalizer;

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

    /**
     * @param mixed $definition Definition to allow registering extensions for.
     */
    public function __construct($definition)
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

    public function resolve(ContainerInterface $container)
    {
        $service = (Normalizer::normalize($this->definition))->resolve($container);

        foreach ($this->extensions as $extension) {
            $service = $extension($container->get(ContainerInterface::class), $service);
        }

        return $service;
    }
}
