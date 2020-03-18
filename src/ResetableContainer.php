<?php

namespace Yiisoft\Di;

final class ResetableContainer extends Container implements Resetable
{
    public function reset(): void
    {
        foreach ($this->instances as $service => $instance) {
            if ($instance instanceof Resetable) {
                $instance->reset();
                continue;
            }

            unset($this->instances[$service]);
        }
    }
}
