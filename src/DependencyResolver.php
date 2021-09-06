<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;

/**
 * @internal
 */
final class DependencyResolver implements DependencyResolverInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @return mixed|object
     *
     * @psalm-suppress InvalidThrow
     */
    public function resolve(string $id)
    {
        return $this->container->get($id);
    }

    public function resolveReference(string $id)
    {
        return $this->resolve($id);
    }
}
