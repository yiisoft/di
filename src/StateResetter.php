<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

use function is_int;
use function is_object;
use function sprintf;

/**
 * State resetter allows resetting state of the services that are currently stored in the container and have "reset"
 * callback defined. The reset should be triggered after each request-response cycle in case you build long-running
 * applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/).
 */
final class StateResetter
{
    /**
     * @var Closure[]|self[]
     * @psalm-param array<int|string, Closure|self>
     */
    private array $resetters;

    /**
     * @param ContainerInterface $container Container to reset.
     * @param Closure[]|self[] $resetters Array of reset callbacks or state resetters. A callback with a string
     * key is bound to the service instance with the corresponding ID, so it has access to the private and
     * protected properties of the service, and you can set the initial state of the service efficiently without
     * creating a new instance. A callback with an integer key is called as is, with the container passed to the
     * state resetter as the first argument. A `StateResetter` instance with an integer key is reset by calling
     * its `reset()` method.
     * @psalm-param array<int|string, Closure|self> $resetters
     */
    public function __construct(
        private readonly ContainerInterface $container,
        array $resetters = [],
    ) {
        $this->resetters = $this->prepareResetters($resetters);
    }

    /**
     * Reset the container.
     */
    public function reset(): void
    {
        foreach ($this->resetters as $resetter) {
            if ($resetter instanceof self) {
                $resetter->reset();
                continue;
            }
            $resetter($this->container);
        }
    }

    /**
     * Returns a new instance with the specified resetters added to the existing ones.
     *
     * @param Closure[]|self[] $resetters Array of reset callbacks or state resetters. A callback with a string
     * key is bound to the service instance with the corresponding ID, so it has access to the private and
     * protected properties of the service, and you can set the initial state of the service efficiently without
     * creating a new instance. A callback with an integer key is called as is, with the container passed to the
     * state resetter as the first argument. A `StateResetter` instance with an integer key is reset by calling
     * its `reset()` method.
     * @psalm-param array<int|string, Closure|self> $resetters
     */
    public function withAddedResetters(array $resetters): self
    {
        $new = clone $this;
        $new->resetters = [...$new->resetters, ...$this->prepareResetters($resetters)];
        return $new;
    }

    /**
     * @param Closure[]|self[] $resetters Array of reset callbacks or state resetters. A callback with a string
     * key is bound to the service instance with the corresponding ID, so it has access to the private and
     * protected properties of the service, and you can set the initial state of the service efficiently without
     * creating a new instance. A callback with an integer key is called as is, with the container passed to the
     * state resetter as the first argument. A `StateResetter` instance with an integer key is reset by calling
     * its `reset()` method.
     * @psalm-param array<int|string, Closure|self> $resetters
     *
     * @deprecated Use the constructor instead. It will be removed in the next major version.
     */
    public function setResetters(array $resetters): void
    {
        $this->resetters = $this->prepareResetters($resetters);
    }

    /**
     * @param Closure[]|self[] $resetters Array of reset callbacks or state resetters.
     * @psalm-param array<int|string, Closure|self> $resetters
     *
     * @return Closure[]|self[] The processed resetters.
     * @psalm-return list<Closure|self>
     */
    private function prepareResetters(array $resetters): array
    {
        $result = [];
        foreach ($resetters as $serviceId => $callback) {
            if (is_int($serviceId)) {
                if (!$callback instanceof self && !$callback instanceof Closure) {
                    throw new InvalidArgumentException(sprintf(
                        'State resetter should be an instance of "%s" or a closure, "%s" given.',
                        self::class,
                        get_debug_type($callback),
                    ));
                }
                $result[] = $callback;
                continue;
            }

            if (!$callback instanceof Closure) {
                throw new InvalidArgumentException(
                    'Callback for state resetter should be closure in format '
                    . '`function (ContainerInterface $container): void`. '
                    . 'Got "' . get_debug_type($callback) . '".',
                );
            }

            $instance = $this->container->get($serviceId);
            if (!is_object($instance)) {
                throw new InvalidArgumentException(
                    'State resetter supports resetting objects only. Container returned '
                    . get_debug_type($instance)
                    . '.',
                );
            }

            /** @var Closure */
            $result[] = $callback->bindTo($instance, $instance::class);
        }
        return $result;
    }
}
