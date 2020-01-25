<?php

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use League\Container\Container;

/**
 * Test the League PSR-11 Container.
 */
class LeaguePsrContainerTest extends AbstractPsrContainerTest
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        return $this->setupContainer(new Container(), $definitions);
    }

    public function setupContainer(ContainerInterface $container, iterable $definitions = []): ContainerInterface
    {
        foreach ($definitions as $id => $definition) {
            $container->add($id, $definition);
        }

        return $container;
    }
}
