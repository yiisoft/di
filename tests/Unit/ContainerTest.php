<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\AbstractContainerConfigurator;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Di\Tests\Support\A;
use Yiisoft\Di\Tests\Support\B;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarFactory;
use Yiisoft\Di\Tests\Support\ColorInterface;
use Yiisoft\Di\Tests\Support\ColorPink;
use Yiisoft\Di\Tests\Support\ConstructorTestClass;
use Yiisoft\Di\Tests\Support\Cycle\Chicken;
use Yiisoft\Di\Tests\Support\Cycle\Egg;
use Yiisoft\Di\Tests\Support\EngineFactory;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\InvokeableCarFactory;
use Yiisoft\Di\Tests\Support\MethodTestClass;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Di\Tests\Support\TreeItem;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Factory\Definitions\ValueDefinition;
use Yiisoft\Factory\Exceptions\CircularReferenceException;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Exceptions\NotFoundException;
use Yiisoft\Injector\Injector;

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

    public function testIntegerKeys(): void
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container([
            EngineMarkOne::class,
            EngineMarkTwo::class,
        ]);

        $container->get(Car::class);
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

    public function testHas(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
        ]);

        $this->assertFalse($container->has('non_existing'));
        $this->assertTrue($container->has(EngineMarkOne::class));
        $this->assertTrue($container->has(EngineInterface::class));
        $this->assertFalse($container->has(ColorInterface::class));
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

    public function testNestedContainer(): void
    {
        $container = new Container([
            ContainerInterface::class => function (ContainerInterface $container) {
                return new Container([
                    ColorInterface::class => ColorPink::class,
                    'engine' => fn (EngineInterface $engine) => $engine,
                ], [], $container);
            },
            EngineInterface::class => EngineMarkOne::class,
        ]);

        $newContainer = $container->get(ContainerInterface::class);
        $this->assertNotSame($container, $newContainer);
        $this->assertFalse($newContainer->has(EngineInterface::class));
        $engine = $newContainer->get('engine');
        $this->assertInstanceOf(EngineMarkOne::class, $engine);
        $this->assertSame($engine, $newContainer->get(Car::class)->getEngine());
        $this->assertInstanceOf(ColorPink::class, $newContainer->get(ColorInterface::class));
        $this->expectException(NotFoundException::class);
        $this->assertInstanceOf(ColorPink::class, $container->get(ColorInterface::class));
    }

    public function testNestedContainerInProvider(): void
    {
        $container = new Container(
            [],
            [
                new class() extends ServiceProvider {
                    public function register(Container $container): void
                    {
                        $container->set(ContainerInterface::class, static function (ContainerInterface $container) {
                            return $container->get('new-container');
                        });
                    }
                },
                new class() extends ServiceProvider {
                    public function register(Container $container): void
                    {
                        $container->set('new-container', fn (ContainerInterface $container) => new Container([
                            EngineInterface::class => EngineMarkOne::class,
                        ], [], $container));
                    }
                },
                new class() extends ServiceProvider {
                    public function register(Container $container): void
                    {
                        $container->set('container', fn (ContainerInterface $container) => $container);
                    }
                },
                new class() extends ServiceProvider {
                    public function register(Container $container): void
                    {
                        $container->set(B::class, function () {
                            throw new \RuntimeException();
                        });
                    }
                },
            ],
        );

        ### The order is crucial! Problem can appear when resolving 'new-container' first
        $newcontainer = $container->get('new-container');
        $this->assertNotSame($container, $newcontainer);
        $this->assertSame($newcontainer, $container->get(ContainerInterface::class));
        $this->assertSame($newcontainer, $container->get('container'));
        $this->assertInstanceOf(EngineMarkOne::class, $newcontainer->get(EngineInterface::class));
        $this->assertFalse($container->has(EngineInterface::class));
        $this->assertTrue($newcontainer->has(EngineInterface::class));

        $this->expectException(\RuntimeException::class);
        $container->get(B::class);
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

    // See https://github.com/yiisoft/di/issues/157#issuecomment-701458616
    public function testIntegerIndexedConstructorArguments(): void
    {
        $container = new Container([
            'items' => [
                '__class' => ArrayIterator::class,
                '__construct()' => [
                    [],
                    ArrayIterator::STD_PROP_LIST,
                ],
            ],
        ]);

        $items = $container->get('items');

        $this->assertInstanceOf(ArrayIterator::class, $items);
        $this->assertSame(ArrayIterator::STD_PROP_LIST, $items->getFlags());
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

    public function testClosureInConstruct(): void
    {
        $container = new Container([
            'car' => [
                '__class' => Car::class,
                '__construct()' => [
                    static fn (EngineInterface $engine) => $engine,
                ],
            ],
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $car = $container->get('car');
        $engine = $container->get(EngineInterface::class);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testKeepClosureDefinition(): void
    {
        $engine = new EngineMarkOne();
        $closure = fn (EngineInterface $engine) => $engine;

        $container = new Container([
            EngineInterface::class => $engine,
            'closure' => new ValueDefinition($closure),
            'engine' => $closure,
        ]);

        $closure = $container->get('closure');
        $this->assertSame($closure, $container->get('closure'));
        $this->assertSame($engine, $container->get('engine'));
    }

    public function testAlias(): void
    {
        $container = new Container([
            EngineInterface::class => Reference::to('engine'),
            'engine' => Reference::to('engine-mark-one'),
            'engine-mark-one' => EngineMarkOne::class,
        ]);

        $engine1 = $container->get('engine-mark-one');
        $engine2 = $container->get('engine');
        $engine3 = $container->get(EngineInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame($engine1, $engine2);
        $this->assertSame($engine2, $engine3);
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
            TreeItem::class => TreeItem::class,
        ]);

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    public function testCallable(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            'test' => fn (ContainerInterface $container) => $container->get(EngineInterface::class),
        ]);

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testCallableWithInjector(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            'car' => fn (CarFactory $factory, Injector $injector) => $injector->invoke([$factory, 'create']),
        ]);

        $engine = $container->get(EngineInterface::class);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testCallableWithArgs(): void
    {
        $container = new Container([
            'engine1' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkOne::NAME),
            'engine2' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkTwo::NAME),
        ]);
        $engine1 = $container->get('engine1');
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame(EngineMarkOne::NUMBER, $engine1->getNumber());
        $engine2 = $container->get('engine2');
        $this->assertInstanceOf(EngineMarkTwo::class, $engine2);
        $this->assertSame(EngineMarkTwo::NUMBER, $engine2->getNumber());
    }

    public function testCallableWithDependencies(): void
    {
        $container = new Container([
            'car1' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName($engineFactory, EngineMarkOne::NAME),
            'car2' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName($engineFactory, EngineMarkTwo::NAME),
        ]);
        $car1 = $container->get('car1');
        $this->assertInstanceOf(Car::class, $car1);
        $this->assertInstanceOf(EngineMarkOne::class, $car1->getEngine());
        $car2 = $container->get('car2');
        $this->assertInstanceOf(Car::class, $car2);
        $this->assertInstanceOf(EngineMarkTwo::class, $car2->getEngine());
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
            EngineInterface::class => EngineMarkOne::class,
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
            'engine4' => EngineMarkTwo::class,
            'car' => [
                '__class' => Car::class,
                '__construct()' => [
                    Reference::to('engine1'),
                    [
                        'engine2' => Reference::to('engine2'),
                        'more' => [
                            'engine3' => Reference::to('engine3'),
                            'more' => [
                                'engine4' => Reference::to('engine4'),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $moreEngines = $car->getMoreEngines();
        $this->assertSame($container->get('engine2'), $moreEngines['engine2']);
        $this->assertSame($container->get('engine3'), $moreEngines['more']['engine3']);
        $this->assertSame($container->get('engine4'), $moreEngines['more']['more']['engine4']);
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
            'first-second-third' => static function (ContainerInterface $c) {
                return $c->get('first') . $c->get('second') . $c->get('third');
            },
        ], [], $compositeContainer);

        $compositeContainer->attach($container1);
        $compositeContainer->attach($container2);

        $this->assertSame('first', $compositeContainer->get('first'));
        $this->assertSame('second', $compositeContainer->get('second'));
        $this->assertSame('firstsecondthird', $compositeContainer->get('first-second-third'));
    }

    public function testCircularReferenceExceptionWhileResolvingProviders(): void
    {
        $provider = new class() extends ServiceProvider {
            public function register(Container $container): void
            {
                $container->set(ContainerInterface::class, static function (ContainerInterface $container) {
                    // E.g. wrapping container with proxy class
                    return $container;
                });
                $container->get(B::class);
            }
        };

        $this->expectException(\RuntimeException::class);
        new Container(
            [
                B::class => function () {
                    throw new \RuntimeException();
                },
            ],
            [
                $provider,
            ]
        );
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
