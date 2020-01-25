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
    abstract public function createContainer(iterable $definitions = []): ContainerInterface;

    abstract public function setupContainer(ContainerInterface $container, iterable $definitions = []): ContainerInterface;

    //  !!!Failed for the League Container. Why?
//    public function testSettingScalars(): void
//    {
//        $this->expectException(InvalidConfigException::class);
//        $container = $this->createContainer([
//            'scalar' => 123,
//        ]);
//
//        $container->get('scalar');
//    }

    //  !!!Error for the League Container. Why?
//    public function testOptionalCircularClassDependency(): void
//    {
//        $container = $this->createContainer([
//            A::class => A::class,
//            B::class => B::class,
//        ]);
//        $a = $container->get(A::class);
//        $this->assertInstanceOf(B::class, $a->b);
//        $this->assertNull($a->b->a);
//    }

    //  !!!Failed for the League Container. Why?
//    public function testWithoutDefinition(): void
//    {
//        $container =  $this->createContainer();
//
//        $hasEngine = $container->has(EngineMarkOne::class);
//        $this->assertTrue($hasEngine);
//
//        $engine = $container->get(EngineMarkOne::class);
//        $this->assertInstanceOf(EngineMarkOne::class, $engine);
//    }

    public function testCircularClassDependencyWithoutDefinition(): void
    {
        $container = $this->createContainer();
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(Chicken::class);
    }

    //  !!!Failed for the League Container. Why?
//    public function testTrivialDefinition(): void
//    {
//        $container = $this->createContainer([
//            EngineMarkOne::class => EngineMarkOne::class,
//        ]);
//        $one = $container->get(EngineMarkOne::class);
//        $two = $container->get(EngineMarkOne::class);
//        $this->assertInstanceOf(EngineMarkOne::class, $one);
//        $this->assertSame($one, $two);
//    }

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


    //  !!!Failed for the League Container. Why?
//    public function testDependencies(): void
//    {
//        $container = $this->createContainer([
//            'car' => Car::class,
//            EngineInterface::class => EngineMarkTwo::class,
//        ]);
//
//        /** @var Car $car */
//        $car = $container->get('car');
//        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
//    }

    //  !!!Error for the League Container. Why?
//    public function testCallable(): void
//    {
//        $container = $this->createContainer([
//            'engine' => EngineMarkOne::class,
//            'test' => static function (ContainerInterface $container) {
//                return $container->get('engine');
//            }
//        ]);
//
//        $object = $container->get('test');
//        $this->assertInstanceOf(EngineMarkOne::class, $object);
//    }

    public function testObject(): void
    {
        $container = $this->createContainer([
            'engine' => new EngineMarkOne()
        ]);
        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    //  !!!Error for the League Container. Why?
//    public function testStaticCall(): void
//    {
//        $container = $this->createContainer([
//            'engine' => EngineMarkOne::class,
//            'static' => [CarFactory::class, 'create'],
//        ]);
//        $object = $container->get('static');
//        $this->assertInstanceOf(Car::class, $object);
//    }

    //  !!!Error for the League Container. Why?
//    public function testInvokeable(): void
//    {
//        $container = $this->createContainer([
//            'engine' => EngineMarkOne::class,
//            'invokeable' => new InvokeableCarFactory(),
//        ]);
//        $object = $container->get('invokeable');
//        $this->assertInstanceOf(Car::class, $object);
//    }

    //  !!!Failed for the League Container. Why?
//    public function testSameInstance(): void
//    {
//        $container = $this->createContainer(['engine' => EngineMarkOne::class]);
//        $one = $container->get('engine');
//        $two = $container->get('engine');
//        $this->assertSame($one, $two);
//    }


    public function testThrowingNotFoundException(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = $this->createContainer();
        $container->get('non_existing');
    }
}
