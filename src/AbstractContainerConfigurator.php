<?php

namespace Yiisoft\Di;

abstract class AbstractContainerConfigurator
{
    protected function set(string $id, $definition): void
    {
        throw new \RuntimeException("Method 'set' does not exist.");
    }
}
