<?php

namespace Yiisoft\Di;

final class ResetableContainer extends Container implements ResetableContainerInterface
{
    public function reset(): void
    {
        foreach ($this->instances as $service => $instance) {
            unset($this->instances[$service]);
        }
    }
}
