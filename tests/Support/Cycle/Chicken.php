<?php

namespace Yiisoft\Di\Tests\Support\Cycle;

class Chicken
{
    public function __construct(Egg $egg)
    {
    }
}
