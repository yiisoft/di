<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Factory\DependencyResolverInterface;
use Yiisoft\Injector\Injector;

/**
 * @internal
 */
final class DependencyResolver implements DependencyResolverInterface
{
    private ContainerInterface $container;
    private ?Injector $injector = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $id
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @return mixed|object
     *
     * @psalm-suppress InvalidThrow
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    public function has($id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @param string $id
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @return mixed|object
     *
     * @psalm-suppress InvalidThrow
     */
    public function resolve(string $id)
    {
        return $this->get($id);
    }

    public function invoke(callable $callable)
    {
        return $this->getInjector()->invoke($callable);
    }

    public function shouldCloneOnResolve(): bool
    {
        return false;
    }

    private function getInjector(): Injector
    {
        if ($this->injector === null) {
            $this->injector = new Injector($this->container);
        }
        return $this->injector;
    }
}
