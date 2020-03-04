<?php

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\DeferredServiceProvider;

class CarDeferredProvider extends DeferredServiceProvider
{
    public function provides(): array
    {
        return [
            'car',
            'car-factory',
        ];
    }

    public function register(Container $container): void
    {
        $container->set('car', ['__class' => Car::class]);
        $container->set('car-factory', CarFactory::class);
        $container->set(EngineInterface::class, EngineMarkOne::class);
    }
}
