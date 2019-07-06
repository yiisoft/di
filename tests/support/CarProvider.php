<?php


namespace yii\di\tests\support;

use yii\di\Container;
use yii\di\contracts\ServiceProviderInterace;

class CarProvider implements ServiceProviderInterace
{
    public function register(Container $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
