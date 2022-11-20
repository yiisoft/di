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
            Car::class => static fn (ContainerInterface $container, Car $car) => null,
        ];
    }
}
