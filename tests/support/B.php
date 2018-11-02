<?php


namespace yii\di\tests\support;


class B
{
    public $a;

    public function __construct(?A $a)
    {
        $this->a = $a;
    }
}