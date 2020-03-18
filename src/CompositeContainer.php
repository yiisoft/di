<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Exceptions\NotFoundException;

/**
 * This class implements a composite container for use with containers that support the delegate lookup feature.
 * The goal of the implementation is simplicity.
 */
final class CompositeContainer implements ContainerInterface, Resetable
{
    /**
     * Containers to look into starting from the beginning of the array.
     * @var ContainerInterface[] The list of containers
     */
    private $containers = [];

    public function get($id, array $parameters = [])
    {
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                if ($parameters !== []) {
                    return $container->get($id, $parameters);
                }
                return $container->get($id);
            }
        }
        throw new NotFoundException("No definition for $id");
    }

    public function has($id)
    {
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attaches a container to the composite container.
     * @param ContainerInterface $container
     */
    public function attach(ContainerInterface $container): void
    {
        array_unshift($this->containers, $container);
    }

    /**
     * Removes a container from the list of containers.
     * @param ContainerInterface $container
     */
    public function detach(ContainerInterface $container): void
    {
        foreach ($this->containers as $i => $c) {
            if ($container === $c) {
                unset($this->containers[$i]);
            }
        }
    }

    public function reset(): void
    {
        foreach ($this->containers as $container) {
            if ($container instanceof Resetable) {
                $container->reset();
            }
        }
    }
}
