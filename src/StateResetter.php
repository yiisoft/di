<?php

declare(strict_types=1);

namespace Yiisoft\Di;

/**
 * State resetter allows to reset state of the services that are currently stored in the container and have "reset"
 * callback defined. The reset should be triggered after each request-response cycle in case you build long-running
 * applications with tools like [Swoole](https://www.swoole.co.uk/) or [RoadRunner](https://roadrunner.dev/).
 */
class StateResetter
{
    private array $resetters;

    public function __construct(array $resetters)
    {
        $this->resetters = $resetters;
    }

    public function reset(): void
    {
        foreach ($this->resetters as $resetter) {
            $resetter();
        }
    }

    public function getResetters(): array
    {
        return $this->resetters;
    }
}
