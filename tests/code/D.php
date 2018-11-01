<?php


namespace yii\di\tests\code;


class D
{
    public $c;

    public function __construct(C $c)
    {
        $this->c = $c;
    }

}