<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

final class ContainerDelegator extends AbstractContainerConfigurator
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function delegateLookup(ContainerInterface $container): void
    {
        $this->container->delegateLookup($container);
    }
}
