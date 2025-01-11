<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use stdClass;
use Throwable;
use Yiisoft\Di\BuildingException;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\ExtensibleService;
use Yiisoft\Di\NotFoundException;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\ServiceProviderInterface;
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
use Yiisoft\Di\Tests\Support\EngineFactory;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\EngineStorage;
use Yiisoft\Di\Tests\Support\Garage;
use Yiisoft\Di\Tests\Support\InvokableCarFactory;
use Yiisoft\Di\Tests\Support\MethodTestClass;
use Yiisoft\Di\Tests\Support\NullableConcreteDependency;
use Yiisoft\Di\Tests\Support\OptionalConcreteDependency;
use Yiisoft\Di\Tests\Support\PropertyTestClass;
use Yiisoft\Di\Tests\Support\SportCar;
use Yiisoft\Di\Tests\Support\TreeItem;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorSecondTypeInParamResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorSecondParamNotResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorParamNotResolvable;
use Yiisoft\Di\Tests\Support\UnionTypeInConstructorFirstTypeInParamResolvable;
use Yiisoft\Di\Tests\Support\VariadicConstructor;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;

/**
 * ContainerTest contains tests for \Yiisoft\Di\Container
 */
final class ContainerTest extends TestCase
{
    public function testCanCreateWihtoutConfig(): void
    {
        $this->expectNotToPerformAssertions();

        new Container();
    }

    public function testSettingScalars(): void
    {
        $this->expectException(InvalidConfigException::class);

        $config = ContainerConfig::create()
            ->withDefinitions([
                'scalar' => 123,
            ]);
        $container = new Container($config);

        $container->get('scalar');
    }

    public function testIntegerKeys(): void
    {
        $this->expectException(InvalidConfigException::class);

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class,
                EngineMarkTwo::class,
            ]);
        $container = new Container($config);

        $container->get(Car::class);
    }

    public function testNullableClassDependency(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $container->get(NullableConcreteDependency::class);
    }

    public function testOptionalResolvableClassDependency(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                ])
        );

        $this->assertTrue($container->has(OptionalConcreteDependency::class));
        $service = $container->get(OptionalConcreteDependency::class);
        $this->assertInstanceOf(Car::class, $service->getCar());
    }

    public function testOptionalNotResolvableClassDependency(): void
    {
        $container = new Container();

        $this->assertTrue($container->has(OptionalConcreteDependency::class));
        $service = $container->get(OptionalConcreteDependency::class);
        $this->assertNull($service->getCar());
    }

    public function testOptionalCircularClassDependency(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                A::class => A::class,
                B::class => B::class,
            ]);
        $container = new Container($config);
        $a = $container->get(A::class);
        $this->assertInstanceOf(B::class, $a->b);
        $this->assertNull($a->b->a);
    }

    public static function dataHas(): array
    {
        return [
            [false, 'non_existing'],
            [false, ColorInterface::class],
            [true, Car::class],
            [true, EngineMarkOne::class],
            [true, EngineInterface::class],
            [true, EngineStorage::class],
            [true, Chicken::class],
            [true, TreeItem::class],
        ];
    }

    #[DataProvider('dataHas')]
    public function testHas(bool $expected, $id): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
            ]);
        $container = new Container($config);

        $this->assertSame($expected, $container->has($id));
    }

    public static function dataUnionTypes(): array
    {
        return [
            [UnionTypeInConstructorSecondTypeInParamResolvable::class],
            [UnionTypeInConstructorFirstTypeInParamResolvable::class],
        ];
    }

    #[DataProvider('dataUnionTypes')]
    public function testUnionTypes(string $class): void
    {
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

    public static function dataClassExistButIsNotResolvableWithUnionTypes(): array
    {
        return [
            [UnionTypeInConstructorParamNotResolvable::class],
            [UnionTypeInConstructorSecondParamNotResolvable::class],
        ];
    }

    #[DataProvider('dataClassExistButIsNotResolvableWithUnionTypes')]
    public function testClassExistButIsNotResolvableWithUnionTypes(string $class): void
    {
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
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => EngineMarkOne::class,
            ]);
        $container = new Container($config);

        $one = $container->get(EngineMarkOne::class);
        $two = $container->get(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertSame($one, $two);
    }

    public function testCircularClassDependency(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                Chicken::class => Chicken::class,
                Egg::class => Egg::class,
            ]);
        $container = new Container($config);

        $this->expectException(CircularReferenceException::class);
        $container->get(Chicken::class);
    }

    public function testClassSimple(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine' => EngineMarkOne::class,
            ]);
        $container = new Container($config);
        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine'));
    }

    public function testSetAll(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine1' => EngineMarkOne::class,
                'engine2' => EngineMarkTwo::class,
            ]);
        $container = new Container($config);

        $this->assertInstanceOf(EngineMarkOne::class, $container->get('engine1'));
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get('engine2'));
    }

    public function testClassConstructor(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'constructor_test' => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [42],
                ],
            ]);
        $container = new Container($config);

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame(42, $object->getParameter());
    }

    // See https://github.com/yiisoft/di/issues/157#issuecomment-701458616
    public function testIntegerIndexedConstructorArguments(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'items' => [
                    'class' => ArrayIterator::class,
                    '__construct()' => [
                        [],
                        ArrayIterator::STD_PROP_LIST,
                    ],
                ],
            ]);
        $container = new Container($config);

        $items = $container->get('items');

        $this->assertInstanceOf(ArrayIterator::class, $items);
        $this->assertSame(ArrayIterator::STD_PROP_LIST, $items->getFlags());
    }

    public function testExcessiveConstructorParametersIgnored(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'constructor_test' => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [
                        'parameter' => 42,
                        'surplus1' => 43,
                    ],
                ],
            ]);
        $container = new Container($config);

        /** @var ConstructorTestClass $object */
        $object = $container->get('constructor_test');
        $this->assertSame([42], $object->getAllParameters());
    }

    public function testVariadicConstructorParameters(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                'stringIndexed' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [
                        'first' => 1,
                        'parameters' => [42, 43, 44],
                    ],
                ],
                'integerIndexed' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [1, new EngineMarkOne(), 42, 43, 44],
                ],
            ]);
        $container = new Container($config);

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
        $config = ContainerConfig::create()
            ->withDefinitions([
                'test' => [
                    'class' => VariadicConstructor::class,
                    '__construct()' => [
                        'parameters' => 42,
                        43,
                    ],
                ],
            ]);
        $container = new Container($config);

        $this->expectException(BuildingException::class);
        $this->expectExceptionMessage(
            'Caught unhandled error "Arguments indexed both by name and by position are not allowed in the same array." while building "test".'
        );
        $container->get('test');
    }

    public function testClassProperties(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'property_test' => [
                    'class' => PropertyTestClass::class,
                    '$property' => 42,
                ],
            ]);
        $container = new Container($config);

        /** @var PropertyTestClass $object */
        $object = $container->get('property_test');
        $this->assertSame(42, $object->property);
    }

    public function testClassMethods(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'method_test' => [
                    'class' => MethodTestClass::class,
                    'setValue()' => [42],
                ],
            ]);
        $container = new Container($config);

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }

    public function testClosureInConstructor(): void
    {
        $color = static fn () => new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                ConstructorTestClass::class => [
                    'class' => ConstructorTestClass::class,
                    '__construct()' => [$color],
                ],
            ]);
        $container = new Container($config);

        $testClass = $container->get(ConstructorTestClass::class);
        $this->assertSame($color, $testClass->getParameter());
    }

    public function testDynamicClosureInConstruct(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'car' => [
                    'class' => Car::class,
                    '__construct()' => [
                        DynamicReference::to(static fn (EngineInterface $engine) => $engine),
                    ],
                ],
                EngineInterface::class => EngineMarkTwo::class,
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $engine = $container->get(EngineInterface::class);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testKeepClosureDefinition(): void
    {
        $engine = new EngineMarkOne();
        $closure = static fn (EngineInterface $engine) => $engine;

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => $engine,
                'closure' => DynamicReference::to($closure),
                'engine' => $closure,
            ]);
        $container = new Container($config);

        $closure = $container->get('closure');
        $this->assertSame($closure, $container->get('closure'));
        $this->assertSame($engine, $container->get('engine'));
    }

    public function testClosureInProperty(): void
    {
        $color = static fn () => new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                PropertyTestClass::class => [
                    'class' => PropertyTestClass::class,
                    '$property' => $color,
                ],
            ]);
        $container = new Container($config);

        $testClass = $container->get(PropertyTestClass::class);
        $this->assertSame($color, $testClass->property);
    }

    public function testDynamicClosureInProperty(): void
    {
        $color = new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    '$color' => DynamicReference::to(fn () => $color),
                ],
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $this->assertSame($color, $car->getColor());
    }

    public function testClosureInMethodCall(): void
    {
        $color = static fn () => new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                MethodTestClass::class => [
                    'class' => MethodTestClass::class,
                    'setValue()' => [$color],
                ],
            ]);
        $container = new Container($config);

        $testClass = $container->get(MethodTestClass::class);
        $this->assertSame($color, $testClass->getValue());
    }

    public function testDynamicClosureInMethodCall(): void
    {
        $color = new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    'setColor()' => [DynamicReference::to(fn () => $color)],
                ],
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $this->assertSame($color, $car->getColor());
    }

    public function testAlias(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => Reference::to('engine'),
                'engine' => Reference::to('engine-mark-one'),
                'engine-mark-one' => EngineMarkOne::class,
            ]);
        $container = new Container($config);

        $engine1 = $container->get('engine-mark-one');
        $engine2 = $container->get('engine');
        $engine3 = $container->get(EngineInterface::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame($engine1, $engine2);
        $this->assertSame($engine2, $engine3);
    }

    public function testCircularAlias(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine-1' => Reference::to('engine-2'),
                'engine-2' => Reference::to('engine-3'),
                'engine-3' => Reference::to('engine-1'),
            ]);
        $container = new Container($config);

        $this->expectException(CircularReferenceException::class);
        $container->get('engine-1');
    }

    public function testUndefinedDependencies(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'car' => Car::class,
            ]);
        $container = new Container($config);

        $this->expectException(NotFoundException::class);
        $container->get('car');
    }

    public function testDependencies(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'car' => Car::class,
                EngineInterface::class => EngineMarkTwo::class,
            ]);
        $container = new Container($config);

        /** @var Car $car */
        $car = $container->get('car');
        $this->assertEquals(EngineMarkTwo::NAME, $car->getEngineName());
    }

    public function testCircularReference(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                TreeItem::class => TreeItem::class,
            ]);
        $container = new Container($config);

        $this->expectException(CircularReferenceException::class);
        $container->get(TreeItem::class);
    }

    /**
     * @link https://github.com/yiisoft/di/pull/189
     */
    public function testFalsePositiveCircularReferenceWithClassID(): void
    {
        $this->expectNotToPerformAssertions();

        $container = new Container();

        // Build an object
        $container->get(ColorPink::class);

        // set definition to container
        (fn (string $id, $definition) => $this->addDefinition($id, $definition))->call(
            $container,
            ColorPink::class,
            ColorPink::class
        );

        try {
            // Build an object
            $container->get(ColorPink::class);
        } catch (CircularReferenceException) {
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
        } catch (NotFoundException) {
            // It is expected
        }

        // set definition to container
        (fn (string $id, $definition) => $this->addDefinition($id, $definition))->call(
            $container,
            'test',
            ColorPink::class
        );

        try {
            // Build an object
            $container->get('test');
        } catch (CircularReferenceException) {
            $this->fail('Circular reference detected false positively.');
        }
    }

    public function testCallable(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                'test' => fn (ContainerInterface $container) => $container->get(EngineInterface::class),
            ]);
        $container = new Container($config);

        $object = $container->get('test');
        $this->assertInstanceOf(EngineMarkOne::class, $object);
    }

    public function testCallableWithInjector(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                'car' => fn (CarFactory $factory, Injector $injector) => $injector->invoke($factory->create(...)),
            ]);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($engine, $car->getEngine());
    }

    public function testCallableWithArgs(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine1' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkOne::NAME),
                'engine2' => fn (EngineFactory $factory) => $factory->createByName(EngineMarkTwo::NAME),
            ]);
        $container = new Container($config);
        $engine1 = $container->get('engine1');
        $this->assertInstanceOf(EngineMarkOne::class, $engine1);
        $this->assertSame(EngineMarkOne::NUMBER, $engine1->getNumber());
        $engine2 = $container->get('engine2');
        $this->assertInstanceOf(EngineMarkTwo::class, $engine2);
        $this->assertSame(EngineMarkTwo::NUMBER, $engine2->getNumber());
    }

    public function testCallableWithDependencies(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'car1' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName(
                    $engineFactory,
                    EngineMarkOne::NAME
                ),
                'car2' => fn (CarFactory $carFactory, EngineFactory $engineFactory) => $carFactory->createByEngineName(
                    $engineFactory,
                    EngineMarkTwo::NAME
                ),
            ]);
        $container = new Container($config);
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

        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine' => $engine,
            ]);
        $container = new Container($config);

        $object = $container->get('engine');
        $this->assertSame($engine, $object);
    }

    public function testArrayStaticCall(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                'car' => CarFactory::create(...),
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());
    }

    public function testArrayDynamicCall(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                ColorInterface::class => ColorPink::class,
                'car' => [CarFactory::class, 'createWithColor'],
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testArrayDynamicCallWithObject(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                ColorInterface::class => ColorPink::class,
                'car' => [new CarFactory(), 'createWithColor'],
            ]);
        $container = new Container($config);

        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testInvokeable(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine' => EngineMarkOne::class,
                'invokeable' => new InvokableCarFactory(),
            ]);
        $container = new Container($config);

        $object = $container->get('invokeable');
        $this->assertInstanceOf(Car::class, $object);
    }

    public function testReference(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine' => EngineMarkOne::class,
                'color' => ColorPink::class,
                'car' => [
                    'class' => Car::class,
                    '__construct()' => [
                        Reference::to('engine'),
                    ],
                    '$color' => Reference::to('color'),
                ],
            ]);
        $container = new Container($config);
        $object = $container->get('car');
        $this->assertInstanceOf(Car::class, $object);
        $this->assertInstanceOf(ColorPink::class, $object->color);
    }

    public function testReferencesInArrayInDependencies(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
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
            ]);
        $container = new Container($config);
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

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    '$color' => Reference::to(ColorInterface::class),
                ],
            ]);

        $container = new Container($config);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($color, $car->getColor());
    }

    public function testReferencesInMethodCall(): void
    {
        $color = new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                ColorInterface::class => $color,
                'car' => [
                    'class' => Car::class,
                    'setColor()' => [Reference::to(ColorInterface::class)],
                ],
            ]);
        $container = new Container($config);
        $car = $container->get('car');
        $this->assertInstanceOf(Car::class, $car);
        $this->assertSame($color, $car->getColor());
    }

    public function testCallableArrayValueInConstructor(): void
    {
        $array = [
            [EngineMarkTwo::class, 'getNumber'],
        ];

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                Car::class => [
                    'class' => Car::class,
                    '__construct()' => [
                        Reference::to(EngineInterface::class),
                        $array,
                    ],
                ],
            ]);
        $container = new Container($config);

        /** @var Car $object */
        $object = $container->get(Car::class);
        $this->assertSame($array, $object->getMoreEngines());
    }

    public function testSameInstance(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'engine' => EngineMarkOne::class,
            ]);
        $container = new Container($config);

        $one = $container->get('engine');
        $two = $container->get('engine');
        $this->assertSame($one, $two);
    }

    public function testGetByClassIndirectly(): void
    {
        $number = 42;

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                EngineMarkOne::class => [
                    'setNumber()' => [$number],
                ],
            ]);
        $container = new Container($config);

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
        $config = ContainerConfig::create()
            ->withDefinitions([
                'container' => static fn (ContainerInterface $container) => $container,
            ]);
        $container = new Container($config);

        $this->assertSame($container, $container->get('container'));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testTagsInArrayDefinition(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine'],
                ],
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                    'tags' => ['engine'],
                ],
            ]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, $engines[0]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[1]::class);
    }

    public function testTagsInClosureDefinition(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'definition' => fn () => new EngineMarkOne(),
                    'tags' => ['engine'],
                ],
                EngineMarkTwo::class => [
                    'definition' => fn () => new EngineMarkTwo(),
                    'tags' => ['engine'],
                ],
            ]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, $engines[0]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[1]::class);
    }

    public function testTagsMultiple(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine', 'mark_one'],
                ],
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                    'tags' => ['engine'],
                ],
            ]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');
        $markOneEngines = $container->get('tag@mark_one');

        $this->assertIsArray($engines);
        $this->assertSame(EngineMarkOne::class, $engines[0]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[1]::class);
        $this->assertIsArray($markOneEngines);
        $this->assertSame(EngineMarkOne::class, $markOneEngines[0]::class);
        $this->assertCount(1, $markOneEngines);
    }

    public function testTagsEmpty(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                ],
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                ],
            ]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(0, $engines);
    }

    public function testTagsWithExternalDefinition(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine'],
                ],
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                ],
            ])
            ->withTags(['engine' => [EngineMarkTwo::class]]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, $engines[1]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[0]::class);
    }

    public function testTagsWithExternalDefinitionMerge(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['engine'],
                ],
                EngineMarkTwo::class => [
                    'class' => EngineMarkTwo::class,
                    'tags' => ['engine'],
                ],
            ])
            ->withTags(['mark_two' => [EngineMarkTwo::class]]);
        $container = new Container($config);

        $engines = $container->get('tag@engine');
        $markTwoEngines = $container->get('tag@mark_two');

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, $engines[0]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[1]::class);
        $this->assertIsArray($markTwoEngines);
        $this->assertCount(1, $markTwoEngines);
        $this->assertSame(EngineMarkTwo::class, $markTwoEngines[0]::class);
    }

    public function testTagsAsArrayInConstructor(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
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
        $container = new Container($config);

        $engines = $container
            ->get(Car::class)
            ->getMoreEngines();

        $this->assertIsArray($engines);
        $this->assertCount(2, $engines);
        $this->assertSame(EngineMarkOne::class, $engines[0]::class);
        $this->assertSame(EngineMarkTwo::class, $engines[1]::class);
    }

    public static function dataResetter(): array
    {
        return [
            'strict-mode' => [true],
            'non-strict-mode' => [false],
        ];
    }

    #[DataProvider('dataResetter')]
    public function testResetter(bool $strictMode): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => EngineMarkOne::class,
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'reset' => function () {
                        $this->number = 42;
                    },
                ],
            ])
            ->withStrictMode($strictMode);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $this->assertSame(
            42,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $engine->setNumber(45);
        $this->assertSame(
            45,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());
    }

    public function testResetterInDelegates(): void
    {
        $config = ContainerConfig::create()
            ->withDelegates([
                static function (ContainerInterface $container) {
                    $config = ContainerConfig::create()
                        ->withDefinitions([
                            EngineInterface::class => [
                                'class' => EngineMarkOne::class,
                                'setNumber()' => [42],
                                'reset' => function () {
                                    $this->number = 42;
                                },
                            ],
                        ]);
                    return new Container($config);
                },
            ]);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $this->assertSame(
            42,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $engine->setNumber(45);
        $this->assertSame(
            45,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());
    }

    public function testNewContainerDefinitionInDelegates(): void
    {
        $firstContainer = null;
        $secondContainer = null;

        $config = ContainerConfig::create()
            ->withDefinitions([
                ContainerInterface::class => new Container(),
            ])
            ->withDelegates([
                function (ContainerInterface $container) use (&$firstContainer): ContainerInterface {
                    $firstContainer = $container;
                    return new Container();
                },
                function (ContainerInterface $container) use (&$secondContainer): ContainerInterface {
                    $secondContainer = $container;
                    return new Container();
                },
            ]);
        $originalContainer = new Container($config);

        $container = $originalContainer->get(ContainerInterface::class);

        $this->assertNotSame($container, $originalContainer);
        $this->assertSame($container, $firstContainer);
        $this->assertSame($container, $secondContainer);
    }

    public function testResetterInDelegatesWithCustomResetter(): void
    {
        $config = ContainerConfig::create()
            ->withDelegates([
                static function (ContainerInterface $container) {
                    $config = ContainerConfig::create()
                        ->withDefinitions([
                            EngineInterface::class => [
                                'class' => EngineMarkOne::class,
                                'setNumber()' => [42],
                                'reset' => function () {
                                    $this->number = 42;
                                },
                            ],
                        ]);
                    return new Container($config);
                },
            ])
            ->withDefinitions([
                Car::class => [
                    'class' => Car::class,
                    'setColor()' => [new ColorPink()],
                ],
                StateResetter::class => [
                    'class' => StateResetter::class,
                    'setResetters()' => [
                        [
                            Car::class => function () {
                                $this->color = new ColorPink();
                            },
                        ],
                    ],
                ],
            ]);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $this->assertSame(
            42,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $car = $container->get(Car::class);
        $this->assertInstanceOf(
            ColorPink::class,
            $container
                ->get(Car::class)
                ->getColor(),
        );

        $engine->setNumber(45);
        $this->assertSame(
            45,
            $container
                ->get(EngineInterface::class)
                ->getNumber(),
        );

        $car->setColor(new ColorRed());
        $this->assertInstanceOf(
            ColorRed::class,
            $container
                ->get(Car::class)
                ->getColor(),
        );

        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());

        $this->assertSame($car, $container->get(Car::class));
        $this->assertInstanceOf(
            ColorPink::class,
            $container
                ->get(Car::class)
                ->getColor(),
        );
    }

    public static function dataResetterInProviderDefinitions(): array
    {
        return [
            'strict-mode' => [true],
            'non-strict-mode' => [false],
        ];
    }

    #[DataProvider('dataResetterInProviderDefinitions')]
    public function testResetterInProviderDefinitions(bool $strictMode): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                ],
            ])
            ->withProviders([
                new class () implements ServiceProviderInterface {
                    public function getDefinitions(): array
                    {
                        return [
                            StateResetter::class => static function (ContainerInterface $container) {
                                $resetter = new StateResetter($container);
                                $resetter->setResetters([
                                    EngineInterface::class => function () {
                                        $this->number = 42;
                                    },
                                ]);
                                return $resetter;
                            },
                        ];
                    }

                    public function getExtensions(): array
                    {
                        return [];
                    }
                },
            ])
            ->withStrictMode($strictMode);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $engine->setNumber(45);
        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());
    }

    public function testResetterInProviderExtensions(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                ],
            ])
            ->withProviders([
                new class () implements ServiceProviderInterface {
                    public function getDefinitions(): array
                    {
                        return [];
                    }

                    public function getExtensions(): array
                    {
                        return [
                            StateResetter::class => static function (
                                ContainerInterface $container,
                                StateResetter $resetter
                            ) {
                                $resetter->setResetters([
                                    EngineInterface::class => function () {
                                        $this->number = 42;
                                    },
                                ]);
                                return $resetter;
                            },
                        ];
                    }
                },
            ]);
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $engine->setNumber(45);
        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame(42, $engine->getNumber());
    }

    public function testNestedResetter(): void
    {
        $color = new ColorPink();

        $config = ContainerConfig::create()
            ->withDefinitions([
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
        $container = new Container($config);

        $engine = $container->get(EngineInterface::class);
        $car = $container->get(Car::class);
        $this->assertSame($engine, $car->getEngine());
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());

        $engine->setNumber(45);
        $car->setColor(new ColorRed());
        $this->assertSame(
            45,
            $container
                ->get(Car::class)
                ->getEngine()
                ->getNumber(),
        );
        $this->assertSame(
            'red',
            $container
                ->get(Car::class)
                ->getColor()
                ->getColor(),
        );

        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engine, $container->get(EngineInterface::class));
        $this->assertSame($car, $container->get(Car::class));
        $this->assertSame(
            42,
            $car
                ->getEngine()
                ->getNumber(),
        );
        $this->assertSame($color, $car->getColor());
    }

    public function testResetterInCompositeContainer(): void
    {
        $composite = new CompositeContainer();

        $config = ContainerConfig::create()
            ->withDefinitions([
                'engineMarkOne' => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'reset' => function () {
                        $this->number = 42;
                    },
                ],
            ]);
        $firstContainer = new Container($config);

        $config = ContainerConfig::create()
            ->withDefinitions([
                'engineMarkTwo' => [
                    'class' => EngineMarkTwo::class,
                    'setNumber()' => [43],
                    'reset' => function () {
                        $this->number = 43;
                    },
                ],
            ]);
        $secondContainer = new Container($config);
        $composite->attach($firstContainer);
        $composite->attach($secondContainer);

        $engineMarkOne = $composite->get('engineMarkOne');
        $engineMarkTwo = $composite->get('engineMarkTwo');
        $this->assertSame(
            42,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            43,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );

        $engineMarkOne->setNumber(45);
        $engineMarkTwo->setNumber(46);
        $this->assertSame(
            45,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            46,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );

        $composite
            ->get(StateResetter::class)
            ->reset();

        $this->assertSame($engineMarkOne, $composite->get('engineMarkOne'));
        $this->assertSame($engineMarkTwo, $composite->get('engineMarkTwo'));
        $this->assertSame(
            42,
            $composite
                ->get('engineMarkOne')
                ->getNumber(),
        );
        $this->assertSame(
            43,
            $composite
                ->get('engineMarkTwo')
                ->getNumber(),
        );
    }

    public function testCircularReferenceExceptionWhileResolvingProviders(): void
    {
        $provider = new class () implements ServiceProviderInterface {
            public function getDefinitions(): array
            {
                return [
                    // wrapping container with proxy class
                    ContainerInterface::class => static fn (ContainerInterface $container) => $container,
                ];
            }

            public function getExtensions(): array
            {
                return [];
            }
        };

        $this->expectException(BuildingException::class);
        $this->expectExceptionMessage(
            'Caught unhandled error "RuntimeException" while building "Yiisoft\Di\Tests\Support\B".'
        );

        $config = ContainerConfig::create()
            ->withDefinitions([
                B::class => function () {
                    throw new RuntimeException();
                },
            ])
            ->withProviders([$provider]);
        $container = new Container($config);
        $container->get(B::class);
    }

    public function testDifferentContainerWithProviders(): void
    {
        $provider = new class () implements ServiceProviderInterface {
            public function getDefinitions(): array
            {
                return [
                    ContainerInterface::class => static fn (ContainerInterface $container) => new Container(),
                ];
            }

            public function getExtensions(): array
            {
                return [];
            }
        };

        $config = ContainerConfig::create()
            ->withProviders([$provider]);
        $originalContainer = new Container($config);

        $container = $originalContainer->get(ContainerInterface::class);

        $this->assertNotSame($originalContainer, $container);
    }

    public function testErrorOnMethodTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "setId" is not allowed. Did you mean "setId()" or "$setId"?'
        );

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'setId' => [42],
                ],
            ]);
        new Container($config);
    }

    public function testErrorOnPropertyTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'dev' => true,
                ],
            ]);
        new Container($config);
    }

    public function testErrorOnDisallowMeta(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );

        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineInterface::class => [
                    'class' => EngineMarkOne::class,
                    'tags' => ['a', 'b'],
                    'dev' => 42,
                ],
            ]);
        new Container($config);
    }

    public function testDelegateLookup(): void
    {
        $delegate = static function (ContainerInterface $container) {
            $config = ContainerConfig::create()
                ->withDefinitions([
                    EngineInterface::class => EngineMarkOne::class,
                    SportCar::class => ['__construct()' => ['maxSpeed' => 300]],
                ]);
            return new Container($config);
        };

        $config = ContainerConfig::create()
            ->withDefinitions([
                Garage::class => Garage::class,
                EngineInterface::class => EngineMarkTwo::class,
            ])
            ->withValidate(true)
            ->withDelegates([$delegate]);
        $container = new Container($config);

        $garage = $container->get(Garage::class);

        $this->assertInstanceOf(Garage::class, $garage);
        $this->assertInstanceOf(
            EngineMarkOne::class,
            $garage
                ->getCar()
                ->getEngine(),
        );
    }

    public function testNonClosureDelegate(): void
    {
        $config = ContainerConfig::create()
            ->withDelegates([42]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Delegate must be callable in format "function (ContainerInterface $container): ContainerInterface".'
        );
        new Container($config);
    }

    public function testNonContainerDelegate(): void
    {
        $config = ContainerConfig::create()
            ->withDelegates([
                static fn (ContainerInterface $container) => 42,
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Delegate callable must return an object that implements ContainerInterface.'
        );
        new Container($config);
    }

    public function testExtensibleServiceDefinition(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'test' => new ExtensibleService([], 'test'),
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition. ExtensibleService is only allowed in provider extensions.'
        );
        new Container($config);
    }

    public function testWrongTag(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'tags' => ['engine', 42],
                ],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid tag. Expected a string, got 42.'
        );
        new Container($config);
    }

    public function testNumberProvider(): void
    {
        $config = ContainerConfig::create()
            ->withProviders([42]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches(
            '/^Service provider should be a class name or an instance of '
            . preg_quote(ServiceProviderInterface::class, '/')
            . '\. (integer|int) given\.$/'
        );
        new Container($config);
    }

    public function testNonServiceProviderInterfaceProvider(): void
    {
        $config = ContainerConfig::create()
            ->withProviders([stdClass::class]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Service provider should be an instance of ' . ServiceProviderInterface::class . '.' .
            ' stdClass given.'
        );
        new Container($config);
    }

    public function testStrictModeDisabled(): void
    {
        $config = ContainerConfig::create()
            ->withStrictMode(false);
        $container = new Container($config);
        $this->assertTrue($container->has(EngineMarkOne::class));

        $engine = $container->get(EngineMarkOne::class);
        $this->assertInstanceOf(EngineMarkOne::class, $engine);
    }

    public function testStrictModeEnabled(): void
    {
        $config = ContainerConfig::create()
            ->withStrictMode(true);
        $container = new Container($config);
        $this->assertFalse($container->has(EngineMarkOne::class));

        $this->expectException(NotFoundExceptionInterface::class);
        $container->get(EngineMarkOne::class);
    }

    public function testIntegerKeyInExtensions(): void
    {
        $config = ContainerConfig::create()
            ->withProviders([
                new class () implements ServiceProviderInterface {
                    public function getDefinitions(): array
                    {
                        return [];
                    }

                    public function getExtensions(): array
                    {
                        return [
                            23 => static fn (ContainerInterface $container, StateResetter $resetter) => $resetter,
                        ];
                    }
                },
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Extension key must be a service ID as string, 23 given.');
        new Container($config);
    }

    public function testNonCallableExtension(): void
    {
        $config = ContainerConfig::create()
            ->withProviders([
                new class () implements ServiceProviderInterface {
                    public function getDefinitions(): array
                    {
                        return [];
                    }

                    public function getExtensions(): array
                    {
                        return [
                            ColorPink::class => [],
                        ];
                    }
                },
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Extension of service should be callable, array given.');
        new Container($config);
    }

    public function testNonArrayReset(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'reset' => 42,
                ],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: "reset" should be closure, int given.'
        );
        new Container($config);
    }

    public function testNonArrayTags(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => [42],
                    'tags' => 'hello',
                ],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: tags should be array of strings, string given.'
        );
        new Container($config);
    }

    public function testNonArrayArguments(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                EngineMarkOne::class => [
                    'class' => EngineMarkOne::class,
                    'setNumber()' => 42,
                ],
            ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: incorrect method "setNumber()" arguments. Expected array, got "int". Probably you should wrap them into square brackets.',
        );
        $container = new Container($config);
    }

    public static function dataInvalidTags(): array
    {
        return [
            [
                '/^Invalid tags configuration: tag should be string, 42 given\.$/',
                [42 => [EngineMarkTwo::class]],
            ],
            [
                '/^Invalid tags configuration: tag should contain array of service IDs, (integer|int) given\.$/',
                ['engine' => 42],
            ],
            [
                '/^Invalid tags configuration: service should be defined as class string, (integer|int) given\.$/',
                ['engine' => [42]],
            ],
        ];
    }

    #[DataProvider('dataInvalidTags')]
    public function testInvalidTags(string $message, array $tags): void
    {
        $config = ContainerConfig::create()
            ->withTags($tags);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches($message);
        new Container($config);
    }

    public static function dataNotFoundExceptionMessageWithDefinitions(): array
    {
        return [
            'without-definition' => [[]],
            'with-definition' => [[SportCar::class => SportCar::class]],
        ];
    }

    #[DataProvider('dataNotFoundExceptionMessageWithDefinitions')]
    public function testNotFoundExceptionMessageWithDefinitions(array $definitions): void
    {
        $config = ContainerConfig::create()->withDefinitions($definitions);
        $container = new Container($config);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'No definition or class found or resolvable for "'
            . EngineInterface::class
            . '" while building "'
            . SportCar::class
            . '" -> "'
            . EngineInterface::class
            . '".'
        );
        $container->get(SportCar::class);
    }

    public function testNotFoundExceptionWithNotYiiContainer(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                ContainerInterface::class => new SimpleContainer(),
                SportCar::class => SportCar::class,
            ]);
        $container = new Container($config);

        $exception = null;
        try {
            $container->get(SportCar::class);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertSame(
            'No definition or class found or resolvable for "' . SportCar::class . '" while building it.',
            $exception->getMessage()
        );
        $this->assertInstanceOf(
            \Yiisoft\Test\Support\Container\Exception\NotFoundException::class,
            $exception->getPrevious()
        );
    }
}
