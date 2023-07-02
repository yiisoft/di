<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class ContainerInterfaceExtensionProvider implements ServiceProviderInterface
{
    public function getDefinitions(): iterable
    {
        return [];
    }

    public function getExtensions(): iterable
    {
        return [
            ContainerInterface::class => static fn (ContainerInterface $container, ContainerInterface $extended) => $container,
        ];
    }
}
