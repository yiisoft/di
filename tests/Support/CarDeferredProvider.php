<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\DeferredServiceProviderInterface;

class CarDeferredProvider implements DeferredServiceProviderInterface
{
    public function provides(): array
    {
        return [
            'car',
            'car-factory',
        ];
    }

    public function getDefinitions(): array
    {
        return [
            'car' => ['class' => Car::class],
            'car-factory' => CarFactory::class,
            EngineInterface::class => EngineMarkOne::class,
            ];
    }

    public function getExtensions():array
    {
        return [];
    }
}
