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


        $simple1 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
            Car::class => [
                '__class' => Car::class,
            ],
        ], [], $composite);

        $simple2 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
        ], [], $composite);

        $simple3 = new Container([
            PropertyTestClass::class => [
                '__class' => PropertyTestClass::class,
            ],
            EngineInterface::class => EngineMarkOne::class
        ], [], $composite);

        $composite->attach($simple1);
        $composite->attach($simple2, '/a');
        $composite->attach($simple3, '/a/b');

        $this->assertSame($simple1->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));
        $this->assertNotSame($simple2->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));
        $this->assertNotSame($simple3->get(PropertyTestClass::class), $composite->get(PropertyTestClass::class));


        $contextA = $composite->getContextContainer('/a');
        $this->assertTrue($contextA->has(Car::class));
        $this->assertFalse($contextA->has(EngineInterface::class));
        $this->assertNotSame($simple1->get(PropertyTestClass::class), $contextA->get(PropertyTestClass::class));
        $this->assertSame($simple2->get(PropertyTestClass::class), $contextA->get(PropertyTestClass::class));

        $contextAB = $composite->getContextContainer('/a/b');
        $this->assertTrue($contextAB->has(Car::class));
        $this->assertTrue($contextAB->has(EngineInterface::class));
    }
}
