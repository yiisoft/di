<?php

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;

use Yiisoft\Di\Container;

/**
 * Test the Yiisoft PSR-11 Container.
 */
class YiisoftPsrContainerTest extends AbstractPsrContainerTest
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        return new Container($definitions);
    }
}
