<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

interface ContainerProxyInterface extends ContainerInterface
{
    public function isActive(): bool;

    public function withDecoratedServices(array $decoratedServices): ContainerProxyInterface;
}
