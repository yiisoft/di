<?php


namespace yii\di\tests\code;


class A
{
    public $b;

    public function __construct(?B $b)
    {
        $this->b = $b;
    }

}