<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Exception\NotFoundException;

/**
 * This class implements a composite container for use with containers that support the delegate lookup feature.
 * The goal of the implementation is simplicity.
 */
final class CompositeContainer implements ContainerInterface
{
    /**
     * Containers to look into starting from the beginning of the array.
     *
     * @var ContainerInterface[] The list of containers
     */
    private array $containers = [];

    public function get($id)
    {
        if ($id === StateResetter::class) {
            $resetters = [];
            foreach ($this->containers as $container) {
                if ($container->has(StateResetter::class)) {
                    $resetters[] = $container->get(StateResetter::class);
                }
            }
            return new StateResetter($resetters, $this);
        }

        if ($this->isTagAlias($id)) {
            $tags = [];
            foreach ($this->containers as $container) {
                if (!$container instanceof Container) {
                    continue;
                }
                if ($container->has($id)) {
                    $tags = array_merge($container->get($id), $tags);
                }
            }

            return $tags;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }
        throw new NotFoundException($id);
    }

    public function has($id): bool
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

    private function isTagAlias(string $id): bool
    {
        return strpos($id, 'tag@') === 0;
    }
}
