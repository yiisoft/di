<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\AbstractContainerConfigurator;
use Yiisoft\Di\CompositeContainer;
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
        $container = new Container([
            A::class => A::class
        ]);

        $a = $container->get(A::class);
        // Container can not create instance of B since we have not provided a definition.
        $this->assertNull($a->b);
    }

    public function testOptionalCircularClassDependency(): void
    {
        $container = new Container([
            A::class => A::class,
            B::class => B::class
        ]);
        $a = $container->get(A::class);
        $this->assertInstanceOf(B::class, $a->b);
        $this->assertNull($a->b->a);
    }

    public function testWithoutDefinition(): void
    {
        $container = new Container();

        $hasEngine = $container->has(EngineMarkOne::class);
        $this->assertTrue($hasEngine);

        $engine = $container->get(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine);
    }

    public function testCircularClassDependencyWithoutDefinition(): void
    {
        $container = new Container();
        $this->expectException(CircularReferenceException::class);
        $container->get(Chicken::class);
    }

    public function testTrivialDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => EngineMarkOne::class
        ]);

        $one = $container->get(EngineMarkOne::class);
        $two = $container->get(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertSame($one, $two);
    }

    public function testCircularClassDependency(): void
    {
        $container = new Container([
            Chicken::class => Chicken::class,
            Egg::class => Egg::class,
        ]);

        $this->expectException(CircularReferenceException::class);
        $container->get(Chicken::class);
    }

    public function testClassSimple(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class
        ]);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testSetAll(): void
    {
        $container = new Container([
            'engine1' => EngineMarkOne::class,
            'engine2' => EngineMarkTwo::class,
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
    }

    public function testClassConstructor(): void
    {
        $container = new Container([
            'constructor_test' => [
                '__class' => ConstructorTestClass::class,
                '__construct()' => [42],
            ]
        ]);

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    public function testClassProperties(): void
    {
        $container = new Container([
            'property_test' => [
                '__class' => PropertyTestClass::class,
                'property' => 42,
            ]
        ]);

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods(): void
    {
        $container = new Container([
            'method_test' => [
                '__class' => MethodTestClass::class,
                'setValue()' => [42],
            ]
        ]);

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testAlias(): void
    {
        $container = new Container([
            'engine-mark-one' => Reference::to('engine'),
            'engine' => EngineMarkOne::class,
            EngineInterface::class => Reference::to('engine'),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine-mark-one'));
        $this->assertInstanceOf(EngineMarkOne::class, $container->get(EngineInterface::class));
    }

    public function testCircularAlias(): void
    {
        $container = new Container([
            'engine-1' => Reference::to('engine-2'),
            'engine-2' => Reference::to('engine-3'),
            'engine-3' => Reference::to('engine-1')
        ]);

        $this->expectException(CircularReferenceException::class);
        $container->get('engine-1');
    }

    public function testUndefinedDependencies(): void
    {
        $container = new Container([
            'car' => Car::class
        ]);

        $this->expectException(NotFoundException::class);
        $container->get('car');
    }

    public function testDependencies(): void
    {
        $container = new Container([
            'car' => Car::class,
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
    }

    public function testCircularReference(): void
    {
        $container = new Container([
            TreeItem::class, TreeItem::class,
        ]);

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    public function testCallable(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'test' => static function (ContainerInterface $container) {
                return $container->get('engine');
            }
        ]);

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testObject(): void
    {
        $container = new Container([
            'engine' => new EngineMarkOne()
        ]);

        $object = $container->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testStaticCall(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'static' => [CarFactory::class, 'create'],
        ]);

        $object = $container->get('static');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testInvokeable(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'invokeable' => new InvokeableCarFactory(),
        ]);

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

    public function testReferencesInArrayInDependencies(): void
    {
        $container = new Container([
            'engine1' => EngineMarkOne::class,
            'engine2' => EngineMarkTwo::class,
            'engine3' => EngineMarkTwo::class,
            'car' => [
                '__class' => Car::class,
                '__construct()' => [
                    Reference::to('engine1'),
                    [
                        'engine2' => Reference::to('engine2'),
                        'engine3' => Reference::to('engine3'),
                    ],
                ],
            ],
        ]);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $moreEngines = $car->getMoreEngines();
        $this->assertSame($container->get('engine2'), $moreEngines['engine2']);
        $this->assertSame($container->get('engine3'), $moreEngines['engine3']);
    }

    public function testGetByReference(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class,
            'e1'     => Reference::to('engine'),
        ]);
        $one = $container->get(Reference::to('engine'));
        $two = $container->get(Reference::to('e1'));
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
        $this->assertSame($one, $two);
    }

    public function testSameInstance(): void
    {
        $container = new Container([
            'engine' => EngineMarkOne::class
        ]);

        $one = $container->get('engine');
        $two = $container->get('engine');
        $this->assertSame($one, $two);
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
        $this->assertSame($number, $engine->getNumber());
    }

    public function testThrowingNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $container = new Container();
        $container->get('non_existing');
    }

    public function testContainerInContainer(): void
    {
        $container = new Container([
            ContainerInterface::class => Reference::to('container'),
            'container' => static function (ContainerInterface $container) {
                return $container;
            },
        ]);

        $this->assertSame($container, $container->get('container'));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testContainerDelegateLookupContainer(): void
    {
        $rootContainer = new Container([
            EngineInterface::class => EngineMarkTwo::class
        ]);

        $container = new Container([], [], $rootContainer);
        $car = $container->get(Car::class);

        $this->assertSame(Car::class, get_class($car));
        $this->assertSame(EngineMarkTwo::class, get_class($car->getEngine()));
    }

    public function testContainerDelegateLookupToCompositeContainer(): void
    {
        $compositeContainer = new CompositeContainer();

        $container1 = new Container([
            EngineInterface::class => EngineMarkOne::class
        ]);

        $compositeContainer->attach($container1);

        $container = new Container([], [], $compositeContainer);
        $car = $container->get(Car::class);

        $this->assertSame(Car::class, get_class($car));
        $this->assertSame(EngineMarkOne::class, get_class($car->getEngine()));
    }

    public function testContainerDelegateLookupToCompositeContainerViaProxy(): void
    {
        $compositeContainer = new CompositeContainer();
        $container = new Container([
            'car' => Car::class
        ], [], $compositeContainer);
        $engineContainer = new Container([
            EngineInterface::class => EngineMarkOne::class
        ]);
        $proxyContainer = $this->getProxyContainer($container);
        $compositeContainer->attach($proxyContainer);
        $compositeContainer->attach($engineContainer);
        $engine = $compositeContainer->get('car')->getEngine();
        $this->assertSame(EngineMarkOne::class, get_class($engine));
        $this->assertSame('car', $proxyContainer->getLastIds()[0]);
    }

    public function testContainerDelegateLookupToNestedCompositeContainer(): void
    {
        $compositeContainer = new CompositeContainer();
        $nestedCompositeContainer = new CompositeContainer();

        $container1 = new Container([
            EngineInterface::class => EngineMarkOne::class
        ]);

        $compositeContainer->attach($container1);
        $nestedCompositeContainer->attach($compositeContainer);

        $container = new Container([], [], $nestedCompositeContainer);
        $car = $container->get(Car::class);

        $this->assertSame(Car::class, get_class($car));
        $this->assertSame(EngineMarkOne::class, get_class($car->getEngine()));
    }

    public function testContainerComplexDelegateLookup(): void
    {
        $compositeContainer = new CompositeContainer();
        $container1 = new Container([
            'first' => static function () {
                return 'first';
            },
            'third' => static function () {
                return 'third';
            }
        ]);
        $container2 = new Container([
            'second' => static function () {
                return  'second';
            },
            'first-second-third' => static function ($c) {
                return $c->get('first') . $c->get('second') . $c->get('third');
            },
        ], [], $compositeContainer);

        $compositeContainer->attach($container1);
        $compositeContainer->attach($container2);

        $this->assertSame('first', $compositeContainer->get('first'));
        $this->assertSame('second', $compositeContainer->get('second'));
        $this->assertSame('firstsecondthird', $compositeContainer->get('first-second-third'));
    }

    private function getProxyContainer(ContainerInterface $container): ContainerInterface
    {
        return new class($container) extends AbstractContainerConfigurator implements ContainerInterface {
            private ContainerInterface $container;

            private array $lastId = [];

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
                $this->container->delegateLookup($this);
            }


            public function getLastIds(): array
            {
                return $this->lastId;
            }

            public function get($id)
            {
                $this->lastId[] = $id;
                return $this->container->get($id);
            }

            public function has($id): bool
            {
                return $this->container->has($id);
            }
        };
    }
}
