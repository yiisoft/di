<?php

namespace Yiisoft\Di\Tests\Support;

class A
{
    public $b;

    public function __construct(?B $b)
    {
        $this->b = $b;
    }
}
