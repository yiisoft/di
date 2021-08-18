<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarProvider;
use Yiisoft\Di\Tests\Support\CarExtensionProvider;
use Yiisoft\Di\Tests\Support\ColorRed;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Factory\Exception\InvalidConfigException;

/**
 * Test for {@link Container} and {@link \Yiisoft\Di\support\ServiceProvider}
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 */
class ServiceProviderTest extends TestCase
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

    protected function ensureProviderRegisterExtensions($provider): void
    {
        $container = new Container([
            Car::class => Car::class,
            EngineInterface::class => EngineMarkOne::class,
            'sport_car' => SportCar::class,
        ], [$provider]);

        $this->assertTrue($container->has(Car::class));
        $this->assertTrue($container->has(EngineInterface::class));
        $this->assertInstanceOf(Car::class, $container->get(Car::class));
        $this->assertInstanceOf(ColorRed::class, $container->get(Car::class)->getColor());
        $this->assertInstanceOf(EngineMarkTwo::class, $container->get(Car::class)->getEngine());
    }

    protected function ensureProviderRegisterDefinitions($provider): void
    {
        $container = new Container();

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

        $container = new Container([
            Car::class => Car::class,
            'sport_car' => SportCar::class,
        ], [$provider]);

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

    public function testNotExistedExtention()
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container([], [CarProvider::class]);
    }

    public function testExtentionOverride()
    {
        $container = new Container([
            Car::class => Car::class,
            'sport_car' => SportCar::class,
        ], [CarProvider::class, CarExtensionProvider::class]);

        $this->assertInstanceOf(ColorRed::class, $container->get(Car::class)->getColor());
    }
}
