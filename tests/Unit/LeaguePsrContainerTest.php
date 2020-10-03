<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use League\Container\Container;
use Psr\Container\ContainerInterface;

/**
 * Test the League PSR-11 Container.
 */
class LeaguePsrContainerTest extends AbstractPsrContainerTest
{
    public function createContainer(array $definitions = []): ContainerInterface
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
