<?php


namespace yii\di\tests\support;

use yii\di\AbstractContainer;
use yii\di\contracts\ServiceProviderInterface;

class CarProvider implements ServiceProviderInterface
{
    public function register(AbstractContainer $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
