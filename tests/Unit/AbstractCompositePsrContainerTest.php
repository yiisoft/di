<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;

/**
 * General tests for PSR-11 composite container.
 * To be extended for specific containers.
 */
abstract class AbstractCompositePsrContainerTest extends AbstractPsrContainerTest
{
    public function createCompositeContainer(ContainerInterface $attachedContainer): ContainerInterface
    {
        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach($attachedContainer);

        return $compositeContainer;
    }

    public function testAttach(): void
    {
        $compositeContainer = new CompositeContainer();
        $container = new Container(['test' => EngineMarkOne::class]);
        $compositeContainer->attach($container);
        $this->assertTrue($compositeContainer->has('test'));
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get('test'));
    }

    public function testDetach(): void
    {
        $compositeContainer = new CompositeContainer();
        $container = new Container(['test' => EngineMarkOne::class]);
        $compositeContainer->attach($container);
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get('test'));

        $compositeContainer->detach($container);
        $this->expectException(NotFoundExceptionInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get('test'));
    }

    public function testHasDefinition(): void
    {
        $compositeContainer = $this->createContainer([EngineInterface::class => EngineMarkOne::class]);
        $this->assertTrue($compositeContainer->has(EngineInterface::class));

        $container = new Container(['test' => EngineMarkTwo::class]);
        $compositeContainer->attach($container);
        $this->assertTrue($compositeContainer->has('test'));
    }

    public function testGetPriority(): void
    {
        $compositeContainer = $this->createContainer([EngineInterface::class => EngineMarkOne::class]);
        $container = new Container([EngineInterface::class => EngineMarkTwo::class]);
        $compositeContainer->attach($container);
        $this->assertInstanceOf(EngineMarkTwo::class, $compositeContainer->get(EngineInterface::class));

        $containerOne = new Container([EngineInterface::class => EngineMarkOne::class]);
        $containerTwo = new Container([EngineInterface::class => EngineMarkTwo::class]);
        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach($containerOne);
        $compositeContainer->attach($containerTwo);
        $this->assertInstanceOf(EngineMarkTwo::class, $compositeContainer->get(EngineInterface::class));
    }

    public function testTags(): void
    {
        $compositeContainer = new CompositeContainer();
        $firstContainer = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine'],
            ],
        ]);

        $secondContainer = new Container([
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
                'tags' => ['engine'],
            ],
        ]);

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $engines = $compositeContainer->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
    }
}
