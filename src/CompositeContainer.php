<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\Di\Reference\TagReference;

use function is_string;
use function sprintf;

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

    /**
     * @psalm-template T
     * @psalm-param string|class-string<T> $id
     * @psalm-return ($id is class-string ? T : mixed)
     */
    public function get($id)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!is_string($id)) {
            throw new InvalidArgumentException(
                sprintf(
                    'ID must be a string, %s given.',
                    get_debug_type($id)
                )
            );
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

        if (TagReference::isTagAlias($id)) {
            $tags = [];
            foreach ($this->containers as $container) {
                if (!$container instanceof Container) {
                    continue;
                }
                if ($container->has($id)) {
                    /** @psalm-suppress MixedArgument `Container::get()` always return array for tag */
                    array_unshift($tags, $container->get($id));
                }
            }

            /** @psalm-suppress MixedArgument `Container::get()` always return array for tag */
            return array_merge(...$tags);
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                /** @psalm-suppress MixedReturnStatement */
                return $container->get($id);
            }
        }

        // Collect details from containers
        $exceptions = [];
        foreach ($this->containers as $container) {
            $hasException = false;
            try {
                $container->get($id);
            } catch (Throwable $t) {
                $hasException = true;
                $exceptions[] = [$t, $container];
            } finally {
                if (!$hasException) {
                    $exceptions[] = [
                        new RuntimeException(
                            'Container "has()" returned false, but no exception was thrown from "get()".'
                        ),
                        $container,
                    ];
                }
            }
        }

        throw new CompositeNotFoundException($exceptions);
    }

    public function has($id): bool
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!is_string($id)) {
            throw new InvalidArgumentException(
                sprintf(
                    'ID must be a string, %s given.',
                    get_debug_type($id)
                )
            );
        }

        if ($id === StateResetter::class) {
            return true;
        }

        if (TagReference::isTagAlias($id)) {
            foreach ($this->containers as $container) {
                if (!$container instanceof Container) {
                    continue;
                }
                if ($container->has($id)) {
                    return true;
                }
            }
            return false;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attaches a container to the composite container.
     */
    public function attach(ContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * Removes a container from the list of containers.
     */
    public function detach(ContainerInterface $container): void
    {
        foreach ($this->containers as $i => $c) {
            if ($container === $c) {
                unset($this->containers[$i]);
            }
        }
    }
}
