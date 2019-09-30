<?php
namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\CompositeContextContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\PropertyTestClass;

/**
 * CompositeContextContainerTest contains tests for \Yiisoft\Di\CompositeContextContainer
 */
class CompositeContextContainerTest extends TestCase
{
    public function testContextGet(): void
    {
        $composite = new CompositeContextContainer();

        $container1 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
            Car::class => [
                '__class' => Car::class,
            ],
        ], []);

        $container2 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
        ], []);

        $container3 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
            EngineInterface::class => EngineMarkOne::class
        ], []);

        $composite->attach($container1);
        $composite->attach($container2, '/a');
        $composite->attach($container3, '/a/b');

        $this->assertSame($container1->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));
        $this->assertNotSame($container2->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));
        $this->assertNotSame($container3->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));

        $contextA = $composite->getContextContainer('/a');
        $this->assertTrue($contextA->has(Car::class));
        $this->assertFalse($contextA->has(EngineInterface::class));
        $this->assertNotSame($container1->get(PropertyTestClass::class), $contextA->get(PropertyTestClass::class));
        $this->assertSame($container2->get(PropertyTestClass::class), $contextA->get(PropertyTestClass::class));

        $contextAB = $composite->getContextContainer('/a/b');
        $this->assertTrue($contextAB->has(Car::class));
        $this->assertTrue($contextAB->has(EngineInterface::class));
    }
}
