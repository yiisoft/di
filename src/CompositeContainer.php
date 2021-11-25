<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use function get_class;
use function gettype;
use function is_object;
use function is_string;

/**
 * A composite container for use with containers that support the delegate lookup feature.
 */
final class CompositeContainer implements ContainerInterface
{
    /**
     * Containers to look into starting from the beginning of the array.
     *
     * @var ContainerInterface[] The list of containers.
     */
    private array $containers = [];

    public function get($id)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!is_string($id)) {
            throw new InvalidArgumentException("Id must be a string, {$this->getVariableType($id)} given.");
        }

        if ($id === StateResetter::class) {
            $resetters = [];
            foreach ($this->containers as $container) {
                if ($container->has(StateResetter::class)) {
                    $resetters[] = $container->get(StateResetter::class);
                }
            }
            $stateResetter = new StateResetter($this);
            $stateResetter->setResetters($resetters);

            return $stateResetter;
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


        // Collect details from containers
        $exceptions = [];
        foreach ($this->containers as $container) {
            $hasException = false;
            try {
                $hasException = true;
                $container->get($id);
            } catch (Throwable $t) {
                $exceptions[] = [$t, $container];
            } finally {
                if (!$hasException) {
                    $exceptions[] = [new RuntimeException('Container has() returned false but no exception was thrown from get().'), $container];
                }
            }
        }

        throw new CompositeNotFoundException($exceptions);
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
        $this->containers[] = $container;
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
        return strncmp($id, 'tag@', 4) === 0;
    }

    /**
     * @param mixed $variable
     */
    private function getVariableType($variable): string
    {
        return is_object($variable) ? get_class($variable) : gettype($variable);
    }
}
