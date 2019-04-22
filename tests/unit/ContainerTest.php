<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use yii\di\CompositeContainer;
use yii\di\Container;
use yii\di\definitions\Normalizer;
use yii\di\exceptions\CircularReferenceException;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotFoundException;
use yii\di\Reference;
use yii\di\tests\support\A;
use yii\di\tests\support\B;
use yii\di\tests\support\C;
use yii\di\tests\support\Car;
use yii\di\tests\support\CarFactory;
use yii\di\tests\support\ColorPink;
use yii\di\tests\support\ConstructorTestClass;
use yii\di\tests\support\D;
use yii\di\tests\support\EngineInterface;
use yii\di\tests\support\EngineMarkOne;
use yii\di\tests\support\EngineMarkTwo;
use yii\di\tests\support\GearBox;
use yii\di\tests\support\InvokeableCarFactory;
use yii\di\tests\support\MethodTestClass;
use yii\di\tests\support\PropertyTestClass;
use yii\di\tests\support\TreeItem;

/**
 * ContainerTest contains tests for \yii\di\Container
 */
class ContainerTest extends TestCase
{
    public function testSettingScalars()
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container([
            'scalar' => 123,
        ]);

        $container->get('scalar');
    }

    public function testOptionalClassDependency()
    {
        $this->markTestIncomplete('TODO: implement optional dependencies');
        $container = new Container();
        $container->set(A::class, A::class);

        $a = $container->get(A::class);
        // Container can not create instance of B since we have not provided a definition.
        $this->assertNull($a->b);
    }

    public function testOptionalCircularClassDependency()
    {
        $this->markTestIncomplete('TODO: implement optional dependencies');
        $container = new Container();
        $container->set(A::class, A::class);
        $container->set(B::class, B::class);
        $a = $container->get(A::class);
        $this->assertInstanceOf(B::class, $a->b);
        $this->assertNull($a->b->a);
    }

    public function testCircularClassDependency()
    {
        $container = new Container();
        $container->set(C::class, C::class);
        $container->set(D::class, D::class);
        $this->expectException(CircularReferenceException::class);
        $container->get(C::class);
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

    public function testInvokeable()
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('invokeable', new InvokeableCarFactory());
        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference()
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'color' => ColorPink::class,
            'car' => [
                '__class' => Car::class,
                '__construct()' => [
                    Reference::to('engine')
                ],
                'color' => Reference::to('color')
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
            'e1'     => Reference::to('engine', EngineInterface::class),
        ]);
        $ref = Reference::to('engine', EngineInterface::class);
        $one = $container->get(Reference::to('engine', EngineInterface::class));
        $two = $container->get(Reference::to('e1', EngineInterface::class));
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
        $this->assertEquals(Normalizer::normalize($definition), $container->getDefinition('engine'));
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

    public function testThrowingNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('non_existing');
    }

    public function testContainerInContainer()
    {
        $container = new Container();
        $container->setAll([
            ContainerInterface::class => Reference::to('container'),
            'container' => function (ContainerInterface $container) {
                return $container;
            },
        ]);
        $this->assertSame($container, $container->get('container'));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }
}
