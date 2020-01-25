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
        return $this->setupContainer(new Container(), $definitions);
    }

    public function setupContainer(ContainerInterface $container, iterable $definitions = []): ContainerInterface
    {
        foreach ($definitions as $id => $definition) {
            $container->set($id, $definition);
        }

        return $container;
    }
}
