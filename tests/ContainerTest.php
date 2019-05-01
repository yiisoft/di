<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use yii\di\Container;
use yii\di\exceptions\CircularReferenceException;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotFoundException;
use yii\di\Reference;
use yii\di\tests\code\Car;
use yii\di\tests\code\ColorPink;
use yii\di\tests\code\ConstructorTestClass;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkOne;
use yii\di\tests\code\EngineMarkTwo;
use yii\di\tests\code\GearBox;
use yii\di\tests\code\CarFactory;
use yii\di\tests\code\InvokableCarFactory;
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

    public function testSetAll()
    {
        $container = new Container();
        $container->setAll([
            'engine1' => EngineMarkOne::class,
            'engine2' => EngineMarkTwo::class,
        ]);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
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
        $container->set('engine-mark-one', Reference::to('engine'));
        $container->set('engine', EngineMarkOne::class);
        $container->set(EngineInterface::class, Reference::to('engine'));
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine-mark-one'));
        $this->assertInstanceOf(EngineMarkOne::class, $container->get(EngineInterface::class));
    }

    public function testCircularAlias()
    {
        $container = new Container();
        $container->set('engine-1', Reference::to('engine-2'));
        $container->set('engine-2', Reference::to('engine-3'));
        $container->set('engine-3', Reference::to('engine-1'));

        $this->expectException(CircularReferenceException::class);
        $container->get('engine-1');
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

    public function testInvokable()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('invokable', new InvokableCarFactory());
        $object = $container->get('invokable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference()
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'color' => ColorPink::class,
            'car' => [
                '__class' => Car::class,
                '__construct()' => [new Reference('engine')],
                'color' => new Reference('color')
            ],
        ]);
        $object = $container->get('car');
        $this->assertInstanceOf(Car::class, $object);
        $this->assertInstanceOf(ColorPink::class, $object->color);
    }

    public function testGetByReference()
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'e1'     => Reference::to('engine'),
        ]);
        $ref = Reference::to('engine');
        $one = $container->get(Reference::to('engine'));
        $two = $container->get(Reference::to('e1'));
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
        $this->assertSame($one, $two);
    }

    public function testSameInstance()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $one = $container->get('engine');
        $two = $container->get('engine');
        $this->assertSame($one, $two);
    }

    public function testHasInstance()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $this->assertTrue($container->has('engine'));
        $this->assertFalse($container->hasInstance('engine'));
        $one = $container->get('engine');
        $this->assertTrue($container->hasInstance('engine'));
    }

    public function testInitiable()
    {
        $container = new Container();
        $container->set('gearbox', GearBox::class);
        $manual = new GearBox();
        $this->assertFalse($manual->getInited());
        $automatic = $container->get('gearbox');
        $this->assertTrue($automatic->getInited());
    }

    public function testGetDefinition()
    {
        $definition = [
            '__class' => EngineMarkOne::class,
        ];
        $container = new Container([
            'engine' => $definition,
        ]);
        $container->get('engine');
        $this->assertSame($definition, $container->getDefinition('engine'));
    }

    public function testGetByClassIndirectly()
    {
        $number = 42;
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            EngineMarkOne::class => [
                'setNumber()' => [$number],
            ],
        ]);

        $engine = $container->get(EngineInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine);
        $this->assertSame($number, $engine->getNumer());
    }
    
    public function testClassConstructorWithArrayOfReferences()
    {
        $container = new Container();
        $container->set('constructor_test', [
            '__class' => code\PartCatalog::class,
            '__construct()' => [\yii\di\ReferencingArray::items
                (['markOne' => Reference::to(EngineMarkOne::class),
                 'markTwo' => Reference::to(EngineMarkOne::class)])],
            'created' => \yii\di\CallableReference::to(function(){return new \DateTime();})
        ]);

        /** @var code\PartCatalog $object */
        $object = $container->get('constructor_test');
        $this->assertInstanceOf(EngineMarkOne::class, $object->engines['markOne']);
        $this->assertInstanceOf(EngineMarkOne::class, $object->engines['markTwo']);
        $this->assertInstanceOf(\DateTime::class, $object->created);
    }
}
