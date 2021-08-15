<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

final class CarProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
            'car' => Car::class,
            EngineInterface::class => EngineMarkOne::class,
        ];
    }

    public function getExtensions(): array
    {
        return [
            Car::class => static function (ContainerInterface $container, Car $car) {
                $car->setColor(new ColorPink());
                return $car;
            },
            'sport_car' => static function (ContainerInterface $container, SportCar $car) {
                $car->setColor(new ColorPink());
                return $car;
            },
        ];
    }
}
