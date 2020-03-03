<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

interface ContainerDelegateInterface
{
    public function withRootContainer(ContainerInterface $container): ContainerInterface;
}
