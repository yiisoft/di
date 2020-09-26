<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;

/**
 * This class implements a composite container for use with containers that support the delegate lookup feature.
 * The goal of the implementation is simplicity.
 */
interface CompositeContainerInterface extends ContainerInterface
{
    public function attach(ContainerInterface $container): void;
}
