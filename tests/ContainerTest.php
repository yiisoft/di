<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use yii\di\CircularReferenceException;
use yii\di\Container;
use yii\di\InvalidConfigException;
use yii\di\NotFoundException;
use yii\di\tests\code\Car;
use yii\di\tests\code\ConstructorTestClass;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkOne;
use yii\di\tests\code\EngineMarkTwo;
use yii\di\tests\code\CarFactory;
use yii\di\tests\code\MethodTestClass;
use yii\di\tests\code\PropertyTestClass;
use yii\di\tests\code\TreeItem;

/**
 * ContainerTest contains tests for \yii\di\Container
 */
class ContainerTest extends TestCase
{
    public function testSettingScalars()
    {
        $container = new Container([
            'scalar' => 123,
        ]);

        $this->expectException(InvalidConfigException::class);
        $container->get('scalar');
    }

    public function testThrowingNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('non_existing');
    }

    public function testNestedContainers()
    {
        $parent = new Container();
        $child = new Container([], $parent);

        $parent->set('only_parent', EngineMarkOne::class);
        $parent->set('shared', EngineMarkOne::class);
        $child->set('shared', EngineMarkTwo::class);

        $this->assertInstanceOf(EngineMarkOne::class, $child->get('only_parent'));
        $this->assertInstanceOf(EngineMarkTwo::class, $child->get('shared'));
    }

    public function testClassSimple()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testClassConstructor()
    {
        $container = new Container();
        $container->set('constructor_test', [
            '__class' => ConstructorTestClass::class,
            '__construct()' => [42],
        ]);

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    public function testClassProperties()
    {
        $container = new Container();
        $container->set('property_test', [
            '__class' => PropertyTestClass::class,
            'property' => 42,
        ]);

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods()
    {
        $container = new Container();
        $container->set('method_test', [
            '__class' => MethodTestClass::class,
            'setValue()' => [42],
        ]);

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testAlias()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->setAlias(EngineInterface::class, 'engine');
        $this->assertInstanceOf(EngineMarkOne::class, $container->get(EngineInterface::class));
    }

    public function testUndefinedDependencies()
    {
        $container = new Container();
        $container->set('car', Car::class);

        $this->expectException(NotFoundException::class);
        $container->get('car');
    }

    public function testDependencies()
    {
        $container = new Container();
        $container->set('car', Car::class);
        $container->set(EngineInterface::class, EngineMarkTwo::class);

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
    }

    public function testCircularReference()
    {
        $container = new Container();
        $container->set(TreeItem::class, TreeItem::class);

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    public function testCallable()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('test', function (ContainerInterface $container) {
            return $container->get('engine');
        });

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
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
        $container->set('engine', EngineMarkOne::class);
        $container->set('static', [CarFactory::class, 'create']);
        $object = $container->get('static');
        $this->assertInstanceOf(Car::class, $object);
    }
}
