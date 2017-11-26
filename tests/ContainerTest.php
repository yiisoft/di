<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use yii\di\CircularReferenceException;
use yii\di\NotFoundException;
use yii\di\Container;
use yii\di\tests\code\Car;
use yii\di\tests\code\ConstructorTestClass;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkOne;
use yii\di\tests\code\EngineMarkTwo;
use yii\di\tests\code\CarFactory;
use yii\di\tests\code\InvokeableCarFactory;
use yii\di\tests\code\MethodTestClass;
use yii\di\tests\code\PropertyTestClass;
use yii\di\tests\code\TreeItem;

/**
 * ContainerTest contains tests for \yii\di\SimplerContainer
 */
class ContainerTest extends TestCase
{
    public function testSettingScalars()
    {
        $container = new Container([
            'scalar' => 123,
        ]);

        $value = $container->get('scalar');
        $this->assertSame(123, $value);
    }

    public function testThrowingNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('non_existing');
    }

    public function testNestedContainers()
    {
        $parent = new Container([
            'only_parent' => function() {
                return new EngineMarkOne();
            },
            'shared' => function() {
                return new EngineMarkOne();
            }
        ]);
        $child = new Container([
            'shared' => function() {
                return new EngineMarkTwo();
            }
        ], $parent);

        $this->assertInstanceOf(EngineMarkOne::class, $child->get('only_parent'));
        $this->assertInstanceOf(EngineMarkTwo::class, $child->get('shared'));
    }

    public function testClassSimple()
    {
        $container = new Container();
        $container->set('engine', function() {
            return new EngineMarkOne();
        });
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testClassConstructor()
    {
        $container = new Container();
        $container->set('constructor_test', function() {
            return new ConstructorTestClass(42);
        });

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    public function testClassProperties()
    {
        $container = new Container();
        $container->set('property_test', function() {
            $object = new PropertyTestClass();
            $object->property = 42;
            return $object;
        });

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods()
    {
        $container = new Container();
        $container->set('method_test', function() {
           $object = new MethodTestClass();
           $object->setValue(42);
           return $object;
        });

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testAlias()
    {
        $container = new Container();
        $container->set('engine',function () {
            return new EngineMarkOne();
        });
        $container->setAlias(EngineInterface::class, 'engine');
        $this->assertInstanceOf(EngineMarkOne::class, $container->get(EngineInterface::class));
    }

    public function testDependencies()
    {
        $container = new Container();

        $container->set('engine', function () {
            return new EngineMarkTwo();
        });

        $container->set('car', function (ContainerInterface $container) {
            return new Car($container->get('engine'));
        });

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals('Mark Two', $car->getEngineName());
    }

    public function testCircularReference()
    {
        $container = new Container();
        $container->set('tree_item', function (ContainerInterface $container) {
            $parent = $container->get('tree_item');
            return new TreeItem($parent);
        });

        $this->expectException(CircularReferenceException::class);
        $container->get('tree_item');
    }

    public function testObject()
    {
        $container = new Container();
        $container->set('engine', new EngineMarkOne());
        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testStaticCall()
    {
        $container = new Container();
        $container->set('engine', function() {
            return new EngineMarkOne();
        });
        $container->set('static', [CarFactory::class, 'create']);
        $object = $container->get('static');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testInvokeable()
    {
        $container = new Container();
        $container->set('engine', function () {
            return new EngineMarkOne();
        });
        $container->set('invokeable', new InvokeableCarFactory());
        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }
}
