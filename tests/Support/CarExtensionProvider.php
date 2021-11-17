<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

final class CarExtensionProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [
            Car::class => static function (ContainerInterface $container, Car $car) {
                $car->setColor(new ColorRed());
                return $car;
            },
            EngineInterface::class => static function (ContainerInterface $container, EngineInterface $engine) {
                return $container->get(EngineMarkTwo::class);
            },
        ];
    }
}
