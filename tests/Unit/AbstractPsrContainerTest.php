<?php

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Yiisoft\Di\Tests\Support\A;
use Yiisoft\Di\Tests\Support\B;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarFactory;
use Yiisoft\Di\Tests\Support\ColorPink;
use Yiisoft\Di\Tests\Support\ConstructorTestClass;
use Yiisoft\Di\Tests\Support\Cycle\Chicken;
use Yiisoft\Di\Tests\Support\Cycle\Egg;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\InvokeableCarFactory;
use Yiisoft\Di\Tests\Support\MethodTestClass;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Di\Tests\Support\TreeItem;

/**
 * General tests for PSR-11 container.
 * To be extended for specific containers.
 */
abstract class AbstractPsrContainerTest extends TestCase
{
    abstract public function createContainer(array $definitions = []): ContainerInterface;

    public function testCircularClassDependencyWithoutDefinition(): void
    {
        $container = $this->createContainer();
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(Chicken::class);
    }

    public function testSimpleDefinition(): void
    {
        $container = $this->createContainer([
            EngineInterface::class => EngineMarkOne::class,
        ]);
        $one = $container->get(EngineInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
    }

    public function testClassSimple(): void
    {
        $container = $this->createContainer(['engine' => EngineMarkOne::class]);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testSetAll(): void
    {
        $container = $this->createContainer([
            'engine1' => EngineMarkOne::class,
            'engine2' => EngineMarkTwo::class,
        ]);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
    }

    public function testObject(): void
    {
        $container = $this->createContainer([
            'engine' => new EngineMarkOne()
        ]);
        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testThrowingNotFoundException(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = $this->createContainer();
        $container->get('non_existing');
    }
}
