<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;

/**
 * Test the CompositeContainer over Yiisoft Container.
 */
class CompositePsrContainerOverYiisoftTest extends AbstractCompositePsrContainerTest
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        $container = new Container($definitions);
        return $this->createCompositeContainer($container);
    }
}
