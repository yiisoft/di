<?php

declare(strict_types=1);

namespace Yiisoft\Di;

class StateResetter
{
    private array $resetters;

    public function __construct(array $resetters)
    {
        $this->resetters = $resetters;
    }

    public function reset()
    {
        foreach ($this->resetters as $resetter) {
            $resetter();
        }
    }
}
