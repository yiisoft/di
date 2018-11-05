<?php


namespace yii\di\tests\support;

class A
{
    public $b;

    public function __construct(?B $b)
    {
        $this->b = $b;
    }
}
