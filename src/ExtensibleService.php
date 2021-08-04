<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Definition\DefinitionInterface;
use Yiisoft\Factory\Definition\Normalizer;
use Yiisoft\Factory\DependencyResolverInterface;

final class ExtensibleService implements DefinitionInterface
{
    private $definition;
    private array $extensions;

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
