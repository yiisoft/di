<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use League\Container\Container;
use Psr\Container\ContainerInterface;

/**
 * Test the CompositeContainer over League Container.
 */
class CompositePsrContainerOverLeagueTest extends AbstractCompositePsrContainerTest
{
    public function createContainer(iterable $definitions = []): ContainerInterface
    {
        $container = $this->setupContainer(new Container(), $definitions);
        return $this->createCompositeContainer($container);
    }

    public function setupContainer(ContainerInterface $container, iterable $definitions = []): ContainerInterface
    {
        foreach ($definitions as $id => $definition) {
            $container->add($id, $definition);
        }

        return $container;
    }
}
