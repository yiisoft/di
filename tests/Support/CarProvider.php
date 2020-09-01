<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;

class CarProvider extends ServiceProvider
{
    public function getDefinitions(): array
    {
        return [
            'car' =>  Car::class,
            EngineInterface::class => EngineMarkOne::class,
            ];
    }
}
