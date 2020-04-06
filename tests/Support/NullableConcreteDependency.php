<?php

namespace Yiisoft\Di\Tests\Support;

class NullableConcreteDependency
{
    public function __construct(?Car $car)
    {
    }
}
