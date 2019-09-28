<?php
namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

class CarProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set('car', Car::class);
        $container->set(EngineInterface::class, EngineMarkOne::class);
    }
}
