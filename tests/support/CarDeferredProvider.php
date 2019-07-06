<?php

namespace yii\di\tests\support;

use yii\di\Container;
use yii\di\support\DeferredServiceProviderInterface;

class CarDeferredProvider extends DeferredServiceProviderInterface
{
    public function provides(): array
    {
        return [
            Car::class,
            CarFactory::class,
        ];
    }

    public function register(Container $container): void
    {
        $container->set(Car::class, ['__class' => Car::class]);
        $container->set(CarFactory::class, CarFactory::class);
        $container->set(EngineInterface::class, EngineMarkOne::class);
    }
}
