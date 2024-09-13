<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorParamNotResolvable;
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

        $config = ContainerConfig::create()
            ->withDefinitions([
                'test' => EngineMarkOne::class,
            ]);
        $container = new Container($config);
        $compositeContainer->attach($container);
        $this->assertTrue($compositeContainer->has('test'));
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get('test'));
    }

    public function testDetach(): void
    {
        $compositeContainer = new CompositeContainer();

        $config = ContainerConfig::create()
            ->withDefinitions([
                'test' => EngineMarkOne::class,
            ]);
        $container = new Container($config);
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

        $config = ContainerConfig::create()
            ->withDefinitions([
                'test' => EngineMarkTwo::class,
            ]);
        $container = new Container($config);
        $compositeContainer->attach($container);
        $this->assertTrue($compositeContainer->has('test'));
    }

    public function testGetPriority(): void
    {
        $compositeContainer = $this->createContainer([EngineInterface::class => EngineMarkOne::class]);

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkTwo::class,
            ]);
        $container = new Container($config);
        $compositeContainer->attach($container);
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get(EngineInterface::class));

        $config = ContainerConfig::create()
            ->withDefinitions([EngineInterface::class => EngineMarkOne::class]);
        $containerOne = new Container($config);

        $config = ContainerConfig::create()
            ->withDefinitions([EngineInterface::class => EngineMarkTwo::class]);
        $containerTwo = new Container($config);

        $compositeContainer = new CompositeContainer();
        $compositeContainer->attach($containerOne);
        $compositeContainer->attach($containerTwo);
        $this->assertInstanceOf(EngineMarkOne::class, $compositeContainer->get(EngineInterface::class));
    }

    public function testTags(): void
    {
        $compositeContainer = new CompositeContainer();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine'],
                ],
            ]);
        $firstContainer = new Container($config);

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                    'tags' => ['engine'],
                ],
            ]);
        $secondContainer = new Container($config);

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $engines = $compositeContainer->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, $engines[1]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[0]::class);
    }

    public function testDelegateLookup(): void
    {
        $compositeContainer = new CompositeContainer();
        $firstContainer = new Container(ContainerConfig::create());

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
            ]);
        $secondContainer = new Container($config);

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $car = $compositeContainer->get(Car::class);

        $this->assertInstanceOf(Car::class, $car);
    }

    public function testDelegateLookupUnionTypes(): void
    {
        $compositeContainer = new CompositeContainer();
        $firstContainer = new Container(ContainerConfig::create());

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
            ]);
        $secondContainer = new Container($config);

        $compositeContainer->attach($firstContainer);
        $compositeContainer->attach($secondContainer);

        $car = $compositeContainer->get(UnionTypeInConstructorParamNotResolvable::class);

        $this->assertInstanceOf(UnionTypeInConstructorParamNotResolvable::class, $car);
    }
}
