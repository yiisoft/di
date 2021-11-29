<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;

final class ContainerInterfaceExtensionProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [
            ContainerInterface::class => static function (ContainerInterface $container, ContainerInterface $extended) {
                return $container;
            },
        ];
    }
}
