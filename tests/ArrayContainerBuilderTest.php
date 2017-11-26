<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use yii\di\ArrayContainerBuilder;
use yii\di\CircularReferenceException;
use yii\di\Container;
use yii\di\InvalidConfigException;
use yii\di\NotFoundException;
use yii\di\Reference;
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
 * ArrayContainerBuilderTest contains tests for \yii\di\ArrayContainerBuilder
 */
class ArrayContainerBuilderTest extends TestCase
{
    public function testSettingScalars()
    {
        $container = (new ArrayContainerBuilder([
            'scalar' => 123,
        ]))->build();

        $this->assertSame(123, $container->get('scalar'));
    }

    public function testNestedContainers()
    {
        $parent = (new ArrayContainerBuilder([
            'only_parent' => ['__class' => EngineMarkOne::class],
            'shared' => ['__class' => EngineMarkOne::class],
        ]))->build();

        $child = (new ArrayContainerBuilder([
            'shared' => ['__class' => EngineMarkTwo::class],
        ], $parent))->build();

        $this->assertInstanceOf(EngineMarkOne::class, $child->get('only_parent'));
        $this->assertInstanceOf(EngineMarkTwo::class, $child->get('shared'));
    }

    public function testClassConstructor()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('constructor_test', [
            '__class' => ConstructorTestClass::class,
            '__construct()' => [42],
        ]);
        $container = $builder->build();

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    public function testClassProperties()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('property_test', [
            '__class' => PropertyTestClass::class,
            'property' => 42,
        ]);
        $container = $builder->build();

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('method_test', [
            '__class' => MethodTestClass::class,
            'setValue()' => [42],
        ]);

        $container = $builder->build();

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testAlias()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('engine', ['__class' => EngineMarkOne::class]);
        $builder->set('alias', '@engine');
        $container = $builder->build();
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('alias'));
    }

    public function testCallable()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('engine', ['__class' => EngineMarkOne::class]);
        $builder->set('test', function (ContainerInterface $container) {
            return $container->get('engine');
        });

        $container = $builder->build();
        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testObject()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('engine', new EngineMarkOne());
        $container = $builder->build();
        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testStaticCall()
    {
        $builder = new ArrayContainerBuilder();
        $builder->set('engine', ['__class' => EngineMarkOne::class]);
        $builder->set('static', [CarFactory::class, 'create']);
        $container = $builder->build();

        $object = $container->get('static');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testInvokeable()
    {
        $container = (new ArrayContainerBuilder([
            'engine' => ['__class' => EngineMarkOne::class],
            'invokeable' => new InvokeableCarFactory(),
        ]))->build();
        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference()
    {
        $container = (new ArrayContainerBuilder([
            'engine' => EngineMarkOne::class,
            'car' => [
                '__class' => Car::class,
                '__construct()' => ['@engine'],
            ],
        ]))->build();
        $object = $container->get('car');
        $this->assertInstanceOf(Car::class, $object);
    }
}
