<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Factory\Exceptions\NotFoundException;

/**
 * This class implements a composite container for use with containers that support the delegate lookup feature.
 * The goal of the implementation is simplicity.
 */
class CompositeContainer implements ContainerInterface
{
    /**
     * Containers to look into starting from the beginning of the array.
     *
     * @var ContainerInterface[] The list of containers
     */
    private $containers = [];

    public function get($id)
    {
        foreach ($this->containers as $container) {
            try {
                return $container->get($id);
            } catch (NotFoundExceptionInterface $e) {
                // ignore
            }
        }
        throw new NotFoundException($id, "No definition for $id");
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
     *
     * @param ContainerInterface $container
     */
    public function attach(ContainerInterface $container): void
    {
        array_unshift($this->containers, $container);
    }

    /**
     * Removes a container from the list of containers.
     *
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

    private function getFallback(string $id, array $containers)
    {
        $fallback = $containers;

        foreach ($containers as $i => $container) {
            unset($fallback[$i]);

            try {
                return $container->get($id);
            } catch (NotFoundExceptionInterface $e) {
                $class = $e instanceof NotFoundException && $e->getId() !== null ? $e->getId() : $id;

                return $this->getFallback($class, $fallback);
            }
        }
        throw new NotFoundException($id, "No definition for $id");
    }

    private function hasFallback(string $id, array $containers): bool
    {
        $fallback = $containers;

        foreach ($containers as $i => $container) {
            unset($fallback[$i]);

            if (!$container->has($id)) {
                return $this->hasFallback($id, $fallback);
            }
        }

        return false;
    }
}
