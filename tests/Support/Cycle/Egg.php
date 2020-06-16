<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support\Cycle;

class Egg
{
    public function __construct(Chicken $chicken)
    {
    }
}
