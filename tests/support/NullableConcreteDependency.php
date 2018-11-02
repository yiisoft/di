<?php


namespace yii\di\tests\support;


class NullableConcreteDependency
{
    public function __construct(?Car $car)
    {

    }
}