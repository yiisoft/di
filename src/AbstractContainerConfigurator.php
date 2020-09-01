<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

abstract class AbstractContainerConfigurator
{
    protected function delegateLookup(ContainerInterface $container): void
    {
        throw new \RuntimeException("Method 'delegateLookup' does not exist.");
    }
}
