<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Di\Helpers\DefinitionNormalizer;

/**
 * @internal A wrapper for a service definition that allows registering extensions.
 * An extension is callable that returns a modified service object:
 *
 * ```php
 * static function (ContainerInterface $container, $service) {
 *     return $service->withAnotherOption(42);
 * }
 * ```
 */
final class ExtensibleService implements DefinitionInterface
{
    /**
     * @var callable[]
     */
    private array $extensions = [];

    /**
     * @param mixed $definition Definition to allow registering extensions for.
     */
    public function __construct(
        private readonly mixed $definition,
        private readonly string $id
    ) {
    }

    /**
     * Add an extension.
     *
     * An extension is callable that returns a modified service object:
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

    public function resolve(ContainerInterface $container): mixed
    {
        $service = DefinitionNormalizer::normalize($this->definition, $this->id)
            ->resolve($container);

        foreach ($this->extensions as $extension) {
            $result = $extension($container->get(ContainerInterface::class), $service);
            if ($result === null) {
                continue;
            }

            $service = $result;
        }

        return $service;
    }
}
