<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

interface ContainerDelegateInterface
{
    public function delegateLookup(ContainerInterface $container): ContainerInterface;
}
