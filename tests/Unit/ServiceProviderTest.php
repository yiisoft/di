<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\ServiceProviderInterface;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarProvider;
use Yiisoft\Di\Tests\Support\CarExtensionProvider;
use Yiisoft\Di\Tests\Support\ContainerInterfaceExtensionProvider;
use Yiisoft\Di\Tests\Support\ColorRed;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Di\Tests\Support\MethodTestClass;
use Yiisoft\Di\Tests\Support\NullCarExtensionProvider;
use Yiisoft\Di\Tests\Support\SportCar;
use Yiisoft\Definitions\Exception\InvalidConfigException;

final class ServiceProviderTest extends TestCase
{
    public function testAddProviderByClassName(): void
    {
        $this->ensureProviderRegisterDefinitions(CarProvider::class);
        $this->ensureProviderRegisterExtensions(CarExtensionProvider::class);
    }

    public function testAddProviderByInstance(): void
    {
        $this->ensureProviderRegisterDefinitions(new CarProvider());
        $this->ensureProviderRegisterExtensions(new CarExtensionProvider());
    }

    private function ensureProviderRegisterExtensions($provider): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                Car::class => Car::class,
                EngineInterface::class => EngineMarkOne::class,
                'sport_car' => SportCar::class,
            ])
            ->withProviders([$provider]);
        $container = new Container($config);

        $this->assertTrue($container->has(Car::class));
        $this->assertTrue($container->has(EngineInterface::class));
        $this->assertInstanceOf(Car::class, $container->get(Car::class));
        $this->assertInstanceOf(ColorRed::class, $container->get(Car::class)->getColor());
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get(Car::class)->getEngine());
    }

    private function ensureProviderRegisterDefinitions($provider): void
    {
        $container = new Container(ContainerConfig::create());

        $this->assertFalse(
            $container->has(Car::class),
            'Container should not have Car registered before service provider added due to autoload fallback.'
        );
        $this->assertFalse(
            $container->has('car'),
            'Container should not have "car" registered before service provider added.'
        );
        $this->assertFalse(
            $container->has(EngineInterface::class),
            'Container should not have EngineInterface registered before service provider added.'
        );

        $config = ContainerConfig::create()
            ->withDefinitions([
                Car::class => Car::class,
                'sport_car' => SportCar::class,
            ])
            ->withProviders([$provider]);
        $container = new Container($config);

        // ensure addProvider invoked ServiceProviderInterface::register
        $this->assertTrue(
            $container->has('car'),
            'CarProvider should have registered "car" once it was added to container.'
        );
        $this->assertTrue(
            $container->has(EngineInterface::class),
            'CarProvider should have registered EngineInterface once it was added to container.'
        );
    }

    public function testNotExistedExtension(): void
    {
        $this->expectException(InvalidConfigException::class);
        $config = ContainerConfig::create()
            ->withProviders([
                CarProvider::class,
            ]);
        new Container($config);
    }

    public function testContainerInterfaceExtension(): void
    {
        $this->expectException(InvalidConfigException::class);
        $config = ContainerConfig::create()
            ->withProviders([
                ContainerInterfaceExtensionProvider::class,
            ]);
        new Container($config);
    }

    public function testExtensionOverride(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                Car::class => Car::class,
                'sport_car' => SportCar::class,
            ])
            ->withProviders([
                CarProvider::class,
                CarExtensionProvider::class,
            ]);
        $container = new Container($config);

        $this->assertInstanceOf(ColorRed::class, $container->get(Car::class)->getColor());
    }

    public function testExtensionReturnedNull(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                Car::class => Car::class,
                'sport_car' => SportCar::class,
            ])
            ->withProviders([
                CarProvider::class,
                NullCarExtensionProvider::class,
                CarExtensionProvider::class,
            ]);
        $container = new Container($config);

        $this->assertInstanceOf(ColorRed::class, $container->get(Car::class)->getColor());
    }



    public function testClassMethodsWithExtensible(): void
    {
        $config = ContainerConfig::create()
            ->withDefinitions([
                'method_test' => [
                    'class' => MethodTestClass::class,
                    'setValue()' => [42],
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
                            'method_test' => static fn (ContainerInterface $container, MethodTestClass $class) => $class,
                        ];
                    }
                },
            ]);

        $container = new Container($config);

        /** @var MethodTestClass $object */
        $object = $container->get('method_test');
        $this->assertSame(42, $object->getValue());
    }
}
