<?php


namespace yii\di\tests\support;

use yii\di\Container;
use yii\di\contracts\ServiceProvider;

class CarProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
