<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\AbstractContainerConfigurator;
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

    public function register(AbstractContainerConfigurator $containerConfigurator): void
    {
        $containerConfigurator->set('car', ['class' => Car::class]);
        $containerConfigurator->set('car-factory', CarFactory::class);
        $containerConfigurator->set(EngineInterface::class, EngineMarkOne::class);
    }
}
