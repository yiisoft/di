<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use function get_class;
use function is_int;

/**
 * State resetter allows resetting state of the services that are currently stored in the container and have "reset"
 * callback defined. The reset should be triggered after each request-response cycle in case you build long-running
 * applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/).
 */
final class StateResetter
{
    private array $resetters = [];
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container Container to reset.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * @param array $resetters Array of reset callbacks. Each callback has access to the private and protected
     * properties of the service instance, so you can set initial state of the service efficiently without creating
     * a new instance.
     */
    public function setResetters(array $resetters): void
    {
        $this->resetters = [];
        foreach ($resetters as $serviceId => $callback) {
            if (is_int($serviceId)) {
                $this->resetters[] = $callback;
                continue;
            }
            $instance = $this->container->get($serviceId);
            $this->resetters[] = $callback->bindTo($instance, get_class($instance));
        }
    }
}
