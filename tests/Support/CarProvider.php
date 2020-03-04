<?php

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Di\Support\ServiceProvider;

class CarProvider extends ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set('car', Car::class);
        $container->set(EngineInterface::class, EngineMarkOne::class);
    }
}
