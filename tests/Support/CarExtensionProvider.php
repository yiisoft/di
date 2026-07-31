<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

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
            EngineInterface::class => static fn(ContainerInterface $container, EngineInterface $engine) => $container->get(EngineMarkTwo::class),
        ];
    }
}
