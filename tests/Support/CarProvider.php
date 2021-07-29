<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\AbstractContainerConfigurator;
use Yiisoft\Di\Support\ServiceProvider;

class CarProvider extends ServiceProvider
{
    public function register(AbstractContainerConfigurator $containerConfigurator): void
    {
        $containerConfigurator->set('car', Car::class);
        $containerConfigurator->set(EngineInterface::class, EngineMarkOne::class);
    }
}
