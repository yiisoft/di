<?php
namespace Yiisoft\Di\Tests\Support;

class B
{
    public $a;

    public function __construct(?A $a)
    {
        $this->a = $a;
    }
}
