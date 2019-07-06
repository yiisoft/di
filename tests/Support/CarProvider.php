<?php
namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterace;

class CarProvider implements ServiceProviderInterace
{
    public function register(Container $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
