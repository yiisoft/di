<?php
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
        $container = $this->setupContainer(new Container(), $definitions);
        return $this->createCompositeContainer($container);
    }

    public function setupContainer(ContainerInterface $container, iterable $definitions = []): ContainerInterface
    {
        foreach ($definitions as $id => $definition) {
            $container->set($id, $definition);
        }

        return $container;
    }
}
