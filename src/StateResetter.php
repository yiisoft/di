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
     * @param ContainerInterface $container Container to reset.
     * @param Closure[]|self[] $resetters
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private array $resetters = [],
    ) {}

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
     * @param Closure[]|self[] $resetters Array of reset callbacks. A callback with a string key is bound
     * to the service instance with the corresponding ID, so it has access to the private and protected
     * properties of the service, and you can set the initial state of the service efficiently without
     * creating a new instance. A callback with an integer key is called as is, with the container passed
     * to the state resetter as the first argument.
     * @psalm-param array<int|string, Closure|self> $resetters
     */
    public function setResetters(array $resetters): void
    {
        $this->resetters = [];
        foreach ($resetters as $serviceId => $callback) {
            if (is_int($serviceId)) {
                if (!$callback instanceof self && !$callback instanceof Closure) {
                    throw new InvalidArgumentException(sprintf(
                        'State resetter should be an instance of "%s" or a closure, "%s" given.',
                        self::class,
                        get_debug_type($callback),
                    ));
                }
                $this->resetters[] = $callback;
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
            $this->resetters[] = $callback->bindTo($instance, $instance::class);
        }
    }
}
