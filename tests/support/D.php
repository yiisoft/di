<?php


namespace yii\di\tests\support;


class D
{
    public $c;

    public function __construct(C $c)
    {
        $this->c = $c;
    }

}