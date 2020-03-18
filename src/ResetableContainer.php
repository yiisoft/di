<?php

namespace Yiisoft\Di;

final class ResetableContainer extends Container implements Resetable
{
    public function reset(): void
    {
        foreach ($this->instances as $service => $instance) {
            if (is_object($instance) && $instance instanceof Resetable) {
                $instance->reset();
                continue;
            }

            unset($instance[$service]);
        }
    }
}