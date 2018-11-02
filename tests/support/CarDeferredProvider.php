<?php

namespace yii\di\tests\support;

use yii\di\AbstractContainer;
use yii\di\support\DeferredServiceProvider;

class CarDeferredProvider extends DeferredServiceProvider
{
    public function provides(): array
    {
        return [
            Car::class,
            CarFactory::class,
        ];
    }

    public function register(AbstractContainer $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
        $container->set(EngineInterface::class, EngineMarkOne::class);
    }
}
