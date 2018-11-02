<?php


namespace yii\di\tests\support;


class C
{
    public $d;

    public function __construct(D $d)
    {
        $this->d = $d;
    }
}