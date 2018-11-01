<?php


namespace yii\di\tests\code;


class NullableConcreteDependency
{
    public function __construct(?Car $car)
    {

    }
}