<?php
namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

class CarProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(Car::class, Car::class);
        $container->set(CarFactory::class, CarFactory::class);
    }
}
