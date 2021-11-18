<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class NullCarExtensionProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [
        ];
    }

    public function getExtensions(): array
    {
        return [
            Car::class => static function (ContainerInterface $container, Car $car) {
                return null;
            },
        ];
    }
}
