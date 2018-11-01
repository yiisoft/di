<?php


namespace yii\di\tests\code;


class OptionalConcreteDependency
{
    public function __construct(Car $car = null)
    {

    }
}