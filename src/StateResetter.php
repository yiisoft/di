<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

/**
 * State resetter allows to reset state of the services that are currently stored in the container and have "reset"
 * callback defined. The reset should be triggered after each request-response cycle in case you build long-running
 * applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/).
 */
class StateResetter
{
    private array $resetters;
    private ContainerInterface $container;

    public function __construct(array $resetters, ContainerInterface $container)
    {
        $this->resetters = $resetters;
        $this->container = $container;
    }

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

    public function setResetters(array $resetters): void
    {
        foreach ($resetters as $serviceId => $callback) {
            $instance = $this->container->get($serviceId);
            $this->resetters[] = $callback->bindTo($instance, get_class($instance));
        }
    }
}
