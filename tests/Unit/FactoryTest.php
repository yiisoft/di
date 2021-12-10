<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use stdClass;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Factory;
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
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Injector\Injector;

use function get_class;

/**
 * FactoryTest contains tests for \Yiisoft\Di\Container
 */
final class FactoryTest extends TestCase
{
    public function testTrivialDefinition(): void
    {
        $definitions = [
            EngineInterface::class => EngineMarkOne::class,
        ];
        $factory = new Factory(new Container(ContainerConfig::create()->withDefinitions($definitions)));

        $one = $factory->create(Car::class);
        $two = $factory->create(Car::class);
        $this->assertInstanceOf(Car::class, $one);
        $this->assertInstanceOf(Car::class, $two);
        $this->assertNotSame($one, $two);
    }

    public function testPredefinedDefinition(): void
    {
        $definitions = [
            EngineInterface::class => EngineMarkOne::class,
        ];
        $container = new Container(ContainerConfig::create()->withDefinitions($definitions));
        $factory = new Factory($container);
        $factory = $factory->withDefinitions([
            Car::class => [
                'setColor()' => [new ColorRed()],
            ]
        ]);

        $car = $factory->create(Car::class);
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorRed::class, $car->getColor());
    }
}
