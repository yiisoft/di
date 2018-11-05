<?php


namespace yii\di\tests\support;

use yii\di\Container;
use yii\di\contracts\ServiceProviderInterface;

class CarProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
