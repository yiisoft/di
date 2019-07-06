<?php
namespace Yiisoft\Di\Tests\Cycle;

class Egg
{
    public function __construct(Chicken $chicken)
    {
    }
}
