<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use TypeError;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Di\Tests\Support\A;
use Yiisoft\Di\Tests\Support\B;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarFactory;
use Yiisoft\Di\Tests\Support\ColorInterface;
use Yiisoft\Di\Tests\Support\ColorPink;
use Yiisoft\Di\Tests\Support\ColorRed;
use Yiisoft\Di\Tests\Support\ConstructorTestClass;
use Yiisoft\Di\Tests\Support\Cycle\Chicken;
use Yiisoft\Di\Tests\Support\Cycle\Egg;
use Yiisoft\Di\Tests\Support\DelegateLookupProvider;
use Yiisoft\Di\Tests\Support\EngineFactory;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\EngineStorage;
use Yiisoft\Di\Tests\Support\Garage;
use Yiisoft\Di\Tests\Support\InvokeableCarFactory;
use Yiisoft\Di\Tests\Support\MethodTestClass;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Di\Tests\Support\SportCar;
use Yiisoft\Di\Tests\Support\TreeItem;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorSecondTypeInParamResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorSecondParamNotResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorParamNotResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorFirstTypeInParamResolvable;
use Yiisoft\Di\Tests\Support\VariadicConstructor;
use Yiisoft\Di\Tests\Support\NullableConcreteDependency;
use Yiisoft\Factory\Definition\DynamicReference;
use Yiisoft\Factory\Definition\Reference;
use Yiisoft\Factory\Exception\CircularReferenceException;
use Yiisoft\Factory\Exception\InvalidConfigException;
use Yiisoft\Factory\Exception\NotFoundException;
use Yiisoft\Injector\Injector;

/**
 * ContainerTest contains tests for \Yiisoft\Di\Container
 */
class ContainerTest extends TestCase
{
    public function testSettingScalars(): void
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container(
            [
                'scalar' => 123,
            ]
        );

        $container->get('scalar');
    }

    public function testIntegerKeys(): void
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container(
            [
                EngineMarkOne::class,
                EngineMarkTwo::class,
            ]
        );

        $container->get(Car::class);
    }

    public function testNullableClassDependency()
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $result = $container->get(NullableConcreteDependency::class);
    }

    public function testOptionalCircularClassDependency(): void
    {
        $container = new Container(
            [
                A::class => A::class,
                B::class => B::class,
            ]
        );
        $a = $container->get(A::class);
        $this->assertInstanceOf(B::class, $a->b);
        $this->assertNull($a->b->a);
    }

    public function testHas(): void
    {
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
            ]
        );

        $this->assertFalse($container->has('non_existing'));
        $this->assertFalse($container->has(ColorInterface::class));
        $this->assertTrue($container->has(Car::class));
        $this->assertTrue($container->has(EngineMarkOne::class));
        $this->assertTrue($container->has(EngineInterface::class));
        $this->assertTrue($container->has(EngineStorage::class));
        $this->assertTrue($container->has(Chicken::class));
        $this->assertTrue($container->has(TreeItem::class));
    }

    public function dataUnionTypes(): array
    {
        return [
            [UnionTypeInConstructorSecondTypeInParamResolvable::class],
            [UnionTypeInConstructorFirstTypeInParamResolvable::class],
        ];
    }

    /**
     * @dataProvider dataUnionTypes
     */
    public function testUnionTypes(string $class): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Union types are not supported before PHP 8');
        }

        $container = new Container();

        $this->assertTrue($container->has($class));
    }

    public function testClassExistsButIsNotResolvable(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('non_existing'));
        $this->assertFalse($container->has(Car::class));
        $this->assertFalse($container->has(SportCar::class));
        $this->assertFalse($container->has(NullableConcreteDependency::class));
        $this->assertFalse($container->has(ColorInterface::class));
    }

    public function dataClassExistButIsNotResolvableWithUnionTypes(): array
    {
        return [
            [UnionTypeInConstructorParamNotResolvable::class],
            [UnionTypeInConstructorSecondParamNotResolvable::class],
        ];
    }

    /**
     * @dataProvider dataClassExistButIsNotResolvableWithUnionTypes
     */
    public function testClassExistButIsNotResolvableWithUnionTypes(string $class): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Union types are not supported before PHP 8');
        }

        $container = new Container();

        $this->assertFalse($container->has($class));
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
        $container = new Container(
            [
                EngineMarkOne::class => EngineMarkOne::class,
            ]
        );

        $one = $container->get(EngineMarkOne::class);
        $two = $container->get(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertSame($one, $two);
    }

    public function testCircularClassDependency(): void
    {
        $container = new Container(
            [
                Chicken::class => Chicken::class,
                Egg::class => Egg::class,
            ]
        );

        $this->expectException(CircularReferenceException::class);
        $container->get(Chicken::class);
    }

    public function testClassSimple(): void
    {
        $container = new Container(
            [
                'engine' => EngineMarkOne::class,
            ]
        );
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testSetAll(): void
    {
        $container = new Container(
            [
                'engine1' => EngineMarkOne::class,
                'engine2' => EngineMarkTwo::class,
            ]
        );

        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
    }

    public function testClassConstructor(): void
    {
        $container = new Container(
            [
                'constructor_test' => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [42],
                ],
            ]
        );

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    // See https://github.com/yiisoft/di/issues/157#issuecomment-701458616
    public function testIntegerIndexedConstructorArguments(): void
    {
        $container = new Container(
            [
                'items' => [
                    'class' => ArrayIterator::class,
                    '__construct()' => [
                        [],
                        ArrayIterator::STD_PROP_LIST,
                    ],
                ],
            ]
        );

        $items = $container->get('items');

        $this->assertInstanceOf(ArrayIterator::class, $items);
        $this->assertSame(ArrayIterator::STD_PROP_LIST, $items->getFlags());
    }

    public function testExcessiveConstructorParametersIgnored(): void
    {
        $container = new Container(
            [
                'constructor_test' => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [
                        'parameter' => 42,
                        'surplus1' => 43,
                    ],
                ],
            ]
        );

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame([42], $object->getAllParameters());
    }

    public function testVariadicConstructorParameters(): void
    {
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                'stringIndexed' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [
                        'first' => 1,
                        'parameters' => 42,
                        'second' => 43,
                        'third' => 44,
                    ],
                ],
                'integerIndexed' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [1, new EngineMarkOne(), 42, 43, 44],
                ],
            ]
        );

        $object = $container->get('stringIndexed');
        $this->assertSame(1, $object->getFirst());
        $this->assertSame([42, 43, 44], $object->getParameters());
        $this->assertInstanceOf(EngineMarkOne::class, $object->getEngine());

        $object = $container->get('integerIndexed');
        $this->assertSame(1, $object->getFirst());
        $this->assertInstanceOf(EngineMarkOne::class, $object->getEngine());
        $this->assertSame([42, 43, 44], $object->getParameters());
    }

    public function testMixedIndexedConstructorParametersAreNotAllowed(): void
    {
        $container = new Container(
            [
                'test' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [
                        'parameters' => 42,
                        43,
                    ],
                ],
            ]
        );

        $this->expectException(InvalidConfigException::class);
        $container->get('test');
    }

    public function testClassProperties(): void
    {
        $container = new Container(
            [
                'property_test' => [
                    'class' => PropertyTestClass::class,
                    '$property' => 42,
                ],
            ]
        );

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods(): void
    {
        $container = new Container(
            [
                'method_test' => [
                    'class' => MethodTestClass::class,
                    'setValue()' => [42],
                ],
            ]
        );

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testClosureInConstructor(): void
    {
        $color = fn () => new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                ConstructorTestClass::class => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [$color],
                ],
            ]
        );

        $testClass = $container->get(ConstructorTestClass::class);
        $this->assertSame($color, $testClass->getParameter());
    }

    public function testDynamicClosureInConstruct(): void
    {
        $container = new Container(
            [
                'car' => [
                    'class' => Car::class,
                    '__construct()' => [
                        DynamicReference::to(static fn (EngineInterface $engine) => $engine),
                    ],
                ],
                EngineInterface::class => EngineMarkTwo::class,
            ]
        );

        $car = $container->get('car');
        $engine = $container->get(EngineInterface::class);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testKeepClosureDefinition(): void
    {
        $engine = new EngineMarkOne();
        $closure = static fn (EngineInterface $engine) => $engine;

        $container = new Container(
            [
                EngineInterface::class => $engine,
                'closure' => DynamicReference::to($closure),
                'engine' => $closure,
            ]
        );

        $closure = $container->get('closure');
        $this->assertSame($closure, $container->get('closure'));
        $this->assertSame($engine, $container->get('engine'));
    }

    public function testClosureInProperty(): void
    {
        $color = fn () => new ColorPink();
        $container = new Container(
            [
                PropertyTestClass::class => [
                    'class' => PropertyTestClass::class,
                    '$property' => $color,
                ],
            ]
        );

        $testClass = $container->get(PropertyTestClass::class);
        $this->assertSame($color, $testClass->property);
    }

    public function testDynamicClosureInProperty(): void
    {
        $color = new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    '$color' => DynamicReference::to(fn () => $color),
                ],
            ]
        );

        $car = $container->get('car');
        $this->assertSame($color, $car->getColor());
    }

    public function testClosureInMethodCall(): void
    {
        $color = fn () => new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                MethodTestClass::class => [
                    'class' => MethodTestClass::class,
                    'setValue()' => [$color],
                ],
            ]
        );

        $testClass = $container->get(MethodTestClass::class);
        $this->assertSame($color, $testClass->getValue());
    }

    public function testDynamicClosureInMethodCall(): void
    {
        $color = new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    'setColor()' => [DynamicReference::to(fn () => $color)],
                ],
            ]
        );

        $car = $container->get('car');
        $this->assertSame($color, $car->getColor());
    }

    public function testAlias(): void
    {
        $container = new Container(
            [
                EngineInterface::class => Reference::to('engine'),
                'engine' => Reference::to('engine-mark-one'),
                'engine-mark-one' => EngineMarkOne::class,
            ]
        );

        $engine1 = $container->get('engine-mark-one');
        $engine2 = $container->get('engine');
        $engine3 = $container->get(EngineInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame($engine1, $engine2);
        $this->assertSame($engine2, $engine3);
    }

    public function testCircularAlias(): void
    {
        $container = new Container(
            [
                'engine-1' => Reference::to('engine-2'),
                'engine-2' => Reference::to('engine-3'),
                'engine-3' => Reference::to('engine-1'),
            ]
        );

        $this->expectException(CircularReferenceException::class);
        $container->get('engine-1');
    }

    public function testUndefinedDependencies(): void
    {
        $container = new Container(
            [
                'car' => Car::class,
            ]
        );

        $this->expectException(NotFoundException::class);
        $container->get('car');
    }

    public function testDependencies(): void
    {
        $container = new Container(
            [
                'car' => Car::class,
                EngineInterface::class => EngineMarkTwo::class,
            ]
        );

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
    }

    public function testCircularReference(): void
    {
        $container = new Container(
            [
                TreeItem::class => TreeItem::class,
            ]
        );

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    /**
     * @link https://github.com/yiisoft/di/pull/189
     */
    public function testFalsePositiveCircularReferenceWithClassID(): void
    {
        $this->expectNotToPerformAssertions();

        $container = new Container([]);

        // Build an object
        $container->get(ColorPink::class);

        // set definition to container
        (fn (string $id, $definition) => $this->set($id, $definition))->call(
            $container,
            ColorPink::class,
            ColorPink::class
        );

        try {
            // Build an object
            $container->get(ColorPink::class);
        } catch (CircularReferenceException $e) {
            $this->fail('Circular reference detected false positively.');
        }
    }

    /**
     * @link https://github.com/yiisoft/di/pull/189
     */
    public function testFalsePositiveCircularReferenceWithStringID(): void
    {
        $this->expectNotToPerformAssertions();

        $container = new Container();
        try {
            // Build an object
            $container->get('test');
        } catch (NotFoundException $e) {
            // It is expected
        }

        // set definition to container
        (fn (string $id, $definition) => $this->set($id, $definition))->call($container, 'test', ColorPink::class);

        try {
            // Build an object
            $container->get('test');
        } catch (CircularReferenceException $e) {
            $this->fail('Circular reference detected false positively.');
        }
    }

    public function testCallable(): void
    {
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                'test' => fn (ContainerInterface $container) => $container->get(EngineInterface::class),
            ]
        );

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testCallableWithInjector(): void
    {
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                'car' => fn (CarFactory $factory, Injector $injector) => $injector->invoke([$factory, 'create']),
            ]
        );

        $engine = $container->get(EngineInterface::class);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testCallableWithArgs(): void
    {
        $container = new Container(
            [
                'engine1' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkOne::NAME),
                'engine2' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkTwo::NAME),
            ]
        );
        $engine1 = $container->get('engine1');
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame(EngineMarkOne::NUMBER, $engine1->getNumber());
        $engine2 = $container->get('engine2');
        $this->assertInstanceOf(EngineMarkTwo::class, $engine2);
        $this->assertSame(EngineMarkTwo::NUMBER, $engine2->getNumber());
    }

    public function testCallableWithDependencies(): void
    {
        $container = new Container(
            [
                'car1' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName(
                    $engineFactory,
                    EngineMarkOne::NAME
                ),
                'car2' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName(
                    $engineFactory,
                    EngineMarkTwo::NAME
                ),
            ]
        );
        $car1 = $container->get('car1');
        $this->assertInstanceOf(Car::class, $car1);
        $this->assertInstanceOf(EngineMarkOne::class, $car1->getEngine());
        $car2 = $container->get('car2');
        $this->assertInstanceOf(Car::class, $car2);
        $this->assertInstanceOf(EngineMarkTwo::class, $car2->getEngine());
    }

    public function testObject(): void
    {
        $engine = new EngineMarkOne();
        $container = new Container(
            [
                'engine' => $engine,
            ]
        );

        $object = $container->get('engine');
        $this->assertSame($engine, $object);
    }

    public function testArrayStaticCall(): void
    {
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                'car' => [CarFactory::class, 'create'],
            ]
        );

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());
    }

    public function testArrayDynamicCall(): void
    {
        $container = new Container(
            [
                ColorInterface::class => ColorPink::class,
                'car' => [CarFactory::class, 'createWithColor'],
            ]
        );

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testArrayDynamicCallWithObject(): void
    {
        $container = new Container(
            [
                ColorInterface::class => ColorPink::class,
                'car' => [new CarFactory(), 'createWithColor'],
            ]
        );

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testInvokeable(): void
    {
        $container = new Container(
            [
                'engine' => EngineMarkOne::class,
                'invokeable' => new InvokeableCarFactory(),
            ]
        );

        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference(): void
    {
        $container = new Container(
            [
                'engine' => EngineMarkOne::class,
                'color' => ColorPink::class,
                'car' => [
                    'class' => Car::class,
                    '__construct()' => [
                        Reference::to('engine'),
                    ],
                    '$color' => Reference::to('color'),
                ],
            ]
        );
        $object = $container->get('car');
        $this->assertInstanceOf(Car::class, $object);
        $this->assertInstanceOf(ColorPink::class, $object->color);
    }

    public function testReferencesInArrayInDependencies(): void
    {
        $container = new Container(
            [
                'engine1' => EngineMarkOne::class,
                'engine2' => EngineMarkTwo::class,
                'engine3' => EngineMarkTwo::class,
                'engine4' => EngineMarkTwo::class,
                'car' => [
                    'class' => Car::class,
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
            ]
        );
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $moreEngines = $car->getMoreEngines();
        $this->assertSame($container->get('engine2'), $moreEngines['engine2']);
        $this->assertSame($container->get('engine3'), $moreEngines['more']['engine3']);
        $this->assertSame($container->get('engine4'), $moreEngines['more']['more']['engine4']);
    }

    public function testReferencesInProperties(): void
    {
        $color = new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    '$color' => Reference::to(ColorInterface::class),
                ],
            ]
        );
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($color, $car->getColor());
    }

    public function testReferencesInMethodCall(): void
    {
        $color = new ColorPink();
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    'setColor()' => [Reference::to(ColorInterface::class)],
                ],
            ]
        );
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($color, $car->getColor());
    }

    public function testCallableArrayValueInConstructor()
    {
        $array = [
            [EngineMarkTwo::class, 'getNumber'],
        ];
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                Car::class => [
                    'class' => Car::class,
                    '__construct()' => [
                        Reference::to(EngineInterface::class),
                        $array,
                    ],
                ],
            ]
        );

        /** @var Car $object */
        $object = $container->get(Car::class);
        $this->assertSame($array, $object->getMoreEngines());
    }

    public function testSameInstance(): void
    {
        $container = new Container(
            [
                'engine' => EngineMarkOne::class,
            ]
        );

        $one = $container->get('engine');
        $two = $container->get('engine');
        $this->assertSame($one, $two);
    }

    public function testGetByClassIndirectly(): void
    {
        $number = 42;
        $container = new Container(
            [
                EngineInterface::class => EngineMarkOne::class,
                EngineMarkOne::class => [
                    'setNumber()' => [$number],
                ],
            ]
        );

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
        $container = new Container(
            [
                'container' => static function (ContainerInterface $container) {
                    return $container;
                },
            ]
        );

        $this->assertSame($container, $container->get('container'));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testTagsInArrayDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine'],
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
                'tags' => ['engine'],
            ],
        ]);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
    }

    public function testTagsInClosureDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'definition' => function () {
                    return new EngineMarkOne();
                },
                'tags' => ['engine'],
            ],
            EngineMarkTwo::class => [
                'definition' => function () {
                    return new EngineMarkTwo();
                },
                'tags' => ['engine'],
            ],
        ]);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
    }

    public function testTagsMultiple(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine', 'mark_one'],
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
                'tags' => ['engine'],
            ],
        ]);

        $engines = $container->get('tag@engine');
        $markOneEngines = $container->get('tag@mark_one');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
        $this->assertIsArray($markOneEngines);
        $this->assertSame(EngineMarkOne::class, get_class($markOneEngines[0]));
        $this->assertCount(1, $markOneEngines);
    }

    public function testTagsEmpty(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
            ],
        ]);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(0, $engines);
    }

    public function testTagsWithExternalDefinition(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine'],
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
            ],
        ], [], ['engine' => [EngineMarkTwo::class]]);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[1]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[0]));
    }

    public function testTagsWithExternalDefinitionMerge(): void
    {
        $container = new Container([
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine'],
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
                'tags' => ['engine'],
            ],
        ], [], ['mark_two' => [EngineMarkTwo::class]]);

        $engines = $container->get('tag@engine');
        $markTwoEngines = $container->get('tag@mark_two');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
        $this->assertIsArray($markTwoEngines);
        $this->assertCount(1, $markTwoEngines);
        $this->assertSame(EngineMarkTwo::class, get_class($markTwoEngines[0]));
    }

    public function testTagsAsArrayInConstructor(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['engine'],
            ],
            EngineMarkTwo::class => [
                'class' => EngineMarkTwo::class,
                'tags' => ['engine'],
            ],
            Car::class => [
                '__construct()' => ['moreEngines' => Reference::to('tag@engine')],
            ],
        ]);

        $engines = $container->get(Car::class)->getMoreEngines();

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, get_class($engines[0]));
        $this->assertSame(EngineMarkTwo::class, get_class($engines[1]));
    }

    public function testResetter(): void
    {
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            StateResetterInterface::class => StateResetter::class,
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'setNumber()' => [42],
                'reset' => function () {
                    $this->number = 42;
                },
            ],
        ]);

        $engine = $container->get(EngineInterface::class);
        $this->assertSame(42, $container->get(EngineInterface::class)->getNumber());

        $engine->setNumber(45);
        $this->assertSame(45, $container->get(EngineInterface::class)->getNumber());

        $container->get(StateResetterInterface::class)->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());
    }

    public function testWrongResetter(): void
    {
        $this->expectException(TypeError::class);
        new Container([
            EngineInterface::class => EngineMarkOne::class,
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'setNumber()' => [42],
                'reset' => [34],
            ],
        ]);
    }

    public function testNestedResetter(): void
    {
        $color = new ColorPink();
        $container = new Container([
            EngineInterface::class => EngineMarkOne::class,
            EngineMarkOne::class => [
                'class' => EngineMarkOne::class,
                'setNumber()' => [42],
                'reset' => function () {
                    $this->number = 42;
                },
            ],
            ColorInterface::class => $color,
            Car::class => [
                'class' => Car::class,
                'setColor()' => [DynamicReference::to(fn () => $color)],
                'reset' => function (ContainerInterface $container) {
                    $this->color = $container->get(ColorInterface::class);
                },
            ],
        ]);

        $engine = $container->get(EngineInterface::class);
        $car = $container->get(Car::class);
        $this->assertSame($engine, $car->getEngine());
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());

        $engine->setNumber(45);
        $car->setColor(new ColorRed());
        $this->assertSame(45, $container->get(Car::class)->getEngine()->getNumber());
        $this->assertSame('red', $container->get(Car::class)->getColor()->getColor());

        $container->get(StateResetter::class)->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame($car, $container->get(Car::class));
        $this->assertSame(42, $car->getEngine()->getNumber());
        $this->assertSame($color, $car->getColor());
    }

    public function testResetterInCompositeContainer(): void
    {
        $composite = new CompositeContainer();
        $firstContainer = new Container([
            'engineMarkOne' => [
                'class' => EngineMarkOne::class,
                'setNumber()' => [42],
                'reset' => function () {
                    $this->number = 42;
                },
            ],
        ]);
        $secondContainer = new Container([
            'engineMarkTwo' => [
                'class' => EngineMarkTwo::class,
                'setNumber()' => [43],
                'reset' => function () {
                    $this->number = 43;
                },
            ],
        ]);
        $composite->attach($firstContainer);
        $composite->attach($secondContainer);

        $engineMarkOne = $composite->get('engineMarkOne');
        $engineMarkTwo = $composite->get('engineMarkTwo');
        $this->assertSame(42, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(43, $composite->get('engineMarkTwo')->getNumber());

        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);
        $this->assertSame(45, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(46, $composite->get('engineMarkTwo')->getNumber());

        $composite->get(StateResetter::class)->reset();

        $this->assertSame($engineMarkOne, $composite->get('engineMarkOne'));
        $this->assertSame($engineMarkTwo, $composite->get('engineMarkTwo'));
        $this->assertSame(42, $composite->get('engineMarkOne')->getNumber());
        $this->assertSame(43, $composite->get('engineMarkTwo')->getNumber());
    }

    public function testCircularReferenceExceptionWhileResolvingProviders(): void
    {
        $provider = new class() implements ServiceProviderInterface {
            public function getDefinitions(): array
            {
                return [
                    ContainerInterface::class => static function (ContainerInterface $container) {
                        // E.g. wrapping container with proxy class
                        return $container;
                    },
                ];
            }

            public function getExtensions(): array
            {
                return [];
            }
        };

        $this->expectException(\RuntimeException::class);
        $container = new Container(
            [
                B::class => function () {
                    throw new \RuntimeException();
                },
            ],
            [
                $provider,
            ]
        );
        $container->get(B::class);
    }

    public function testErrorOnMethodTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "setId" is not allowed. Did you mean "setId()" or "$setId"?'
        );

        new Container([
            EngineInterface::class => [
                'class' => EngineMarkOne::class,
                'setId' => [42],
            ],
        ]);
    }

    public function testErrorOnPropertyTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );

        new Container([
            EngineInterface::class => [
                'class' => EngineMarkOne::class,
                'dev' => true,
            ],
        ]);
    }

    public function testErrorOnDisallowMeta(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );

        new Container([
            EngineInterface::class => [
                'class' => EngineMarkOne::class,
                'tags' => ['a', 'b'],
                'dev' => 42,
            ],
        ]);
    }

    public function testDelegateLookup(): void
    {
        $delegate = fn() => new Container([
            EngineInterface::class => EngineMarkOne::class,
            SportCar::class => ['__construct()' => ['maxSpeed' => 300]],
        ]);

        $container = new Container([
            Garage::class => Garage::class,
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $container->addDelegates($delegate);

        $garage = $container->get(Garage::class);

        $this->assertInstanceOf(Garage::class, $garage);
        $this->assertInstanceOf(EngineMarkOne::class, $garage->getCar()->getEngine());
    }
}
