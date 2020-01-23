<?php

namespace Yiisoft\Di\Tests\Support\Cycle;

class Egg
{
    public function __construct(Chicken $chicken)
    {
    }
}
