<?php


namespace yii\di\tests\support;

class OptionalConcreteDependency
{
    public function __construct(Car $car = null)
    {
    }
}
