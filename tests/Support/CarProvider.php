<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Yiisoft\Di\Contracts\ServiceProviderInterface;

class CarProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
            'car' =>  Car::class,
            EngineInterface::class => EngineMarkOne::class,
            ];
    }
}
