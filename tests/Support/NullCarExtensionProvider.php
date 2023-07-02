<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class NullCarExtensionProvider implements ServiceProviderInterface
{
    public function getDefinitions(): iterable
    {
        return [
        ];
    }

    public function getExtensions(): iterable
    {
        return [
            Car::class => static fn (ContainerInterface $container, Car $car) => null,
        ];
    }
}
