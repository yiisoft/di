<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use League\Container\Container;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\CompositeNotFoundException;

/**
 * Test the CompositeContainer over League Container.
 */
final class CompositePsrContainerOverLeagueTest extends AbstractCompositePsrContainerTest
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

    protected function getExpectedNotFoundExceptionMessage(): string
    {
        return 'No definition or class found or resolvable in composite container:
#1: No definition or class found or resolvable for "test" while building "test".
#2: No definition or class found or resolvable for "test" while building "test".';
    }

    public function testNotFoundException(): void
    {
        $compositeContainer = new CompositeContainer();

        $container1 = new Container();
        $container1Id = spl_object_id($container1);
        $container2 = new Container();
        $container2Id = spl_object_id($container2);

        $compositeContainer->attach($container1);
        $compositeContainer->attach($container2);

        $this->expectException(CompositeNotFoundException::class);
        $this->expectExceptionMessage("No definition or class found or resolvable in composite container:\n    1. Container League\Container\Container #$container1Id: Alias (test) is not being managed by the container or delegates\n    2. Container League\Container\Container #$container2Id: Alias (test) is not being managed by the container or delegates");
        $compositeContainer->get('test');
    }
}
