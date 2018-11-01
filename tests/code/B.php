<?php


namespace yii\di\tests\code;


class B
{
    public $a;

    public function __construct(?A $a)
    {
        $this->a = $a;
    }
}