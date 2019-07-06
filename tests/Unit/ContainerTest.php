<?php
namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Exceptions\CircularReferenceException;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Exceptions\NotFoundException;
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
use Yiisoft\Factory\Definitions\Reference;

/**
 * ContainerTest contains tests for \Yiisoft\Di\Container
 */
class ContainerTest extends TestCase
{
    public function testSettingScalars(): void
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container([
            'scalar' => 123,
        ]);

        $container->get('scalar');
    }

    public function testOptionalClassDependency(): void
    {
        $this->markTestIncomplete('TODO: implement optional dependencies');
        $container = new Container();
        $container->set(A::class, A::class);

        $a = $container->get(A::class);
        // Container can not create instance of B since we have not provided a definition.
        $this->assertNull($a->b);
    }

    public function testOptionalCircularClassDependency(): void
    {
        $this->markTestIncomplete('TODO: implement optional dependencies');
        $container = new Container();
        $container->set(A::class, A::class);
        $container->set(B::class, B::class);
        $a = $container->get(A::class);
        $this->assertInstanceOf(B::class, $a->b);
        $this->assertNull($a->b->a);
    }

    public function testCircularClassDependency(): void
    {
        $container = new Container();
        $container->set(Chicken::class, Chicken::class);
        $container->set(Egg::class, Egg::class);
        $this->expectException(CircularReferenceException::class);
        $container->get(Chicken::class);
    }

    public function testClassSimple(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testSetAll(): void
    {
        $container = new Container();
        $container->setMultiple([
            'engine1' => EngineMarkOne::class,
            'engine2' => EngineMarkTwo::class,
        ]);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
    }

    public function testClassConstructor(): void
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

    public function testClassProperties(): void
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

    public function testClassMethods(): void
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

    public function testAlias(): void
    {
        $container = new Container();
        $container->set('engine-mark-one', Reference::to('engine'));
        $container->set('engine', EngineMarkOne::class);
        $container->set(EngineInterface::class, Reference::to('engine'));
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine-mark-one'));
        $this->assertInstanceOf(EngineMarkOne::class, $container->get(EngineInterface::class));
    }

    public function testCircularAlias(): void
    {
        $container = new Container();
        $container->set('engine-1', Reference::to('engine-2'));
        $container->set('engine-2', Reference::to('engine-3'));
        $container->set('engine-3', Reference::to('engine-1'));

        $this->expectException(CircularReferenceException::class);
        $container->get('engine-1');
    }

    public function testUndefinedDependencies(): void
    {
        $container = new Container();
        $container->set('car', Car::class);

        $this->expectException(NotFoundException::class);
        $container->get('car');
    }

    public function testDependencies(): void
    {
        $container = new Container();
        $container->set('car', Car::class);
        $container->set(EngineInterface::class, EngineMarkTwo::class);

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
    }

    public function testCircularReference(): void
    {
        $container = new Container();
        $container->set(TreeItem::class, TreeItem::class);

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    public function testCallable(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('test', static function (ContainerInterface $container) {
            return $container->get('engine');
        });

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testObject(): void
    {
        $container = new Container();
        $container->set('engine', new EngineMarkOne());
        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testStaticCall(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('static', [CarFactory::class, 'create']);
        $object = $container->get('static');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testInvokeable(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $container->set('invokeable', new InvokeableCarFactory());
        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference(): void
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

    public function testGetByReference(): void
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

    public function testSameInstance(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $one = $container->get('engine');
        $two = $container->get('engine');
        $this->assertSame($one, $two);
    }

    public function testHasInstance(): void
    {
        $container = new Container();
        $container->set('engine', EngineMarkOne::class);
        $this->assertTrue($container->has('engine'));
        $this->assertFalse($container->hasInstance('engine'));
        $one = $container->get('engine');
        $this->assertTrue($container->hasInstance('engine'));
    }

    public function testGetByClassIndirectly(): void
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

    public function testThrowingNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('non_existing');
    }

    public function testContainerInContainer(): void
    {
        $container = new Container();
        $container->setMultiple([
            ContainerInterface::class => Reference::to('container'),
            'container' => static function (ContainerInterface $container) {
                return $container;
            },
        ]);
        $this->assertSame($container, $container->get('container'));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }
}
