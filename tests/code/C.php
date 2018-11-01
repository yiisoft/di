<?php


namespace yii\di\tests\code;


class C
{
    public $d;

    public function __construct(D $d)
    {
        $this->d = $d;
    }
}