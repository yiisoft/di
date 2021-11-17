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
class StateResetter
{
    private array $resetters = [];
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
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
