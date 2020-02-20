<?php

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarDeferredProvider;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;

/**
 * Test for {@link \Yiisoft\Di\Support\DeferredServiceProvider}
 */
class DeferredServiceProviderTest extends TestCase
{
    public function testServiceProviderDeferring(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('car'), 'Container should not have "car" before service provider added.');
        $this->assertTrue($container->has(Car::class), 'Container should have Car before service provider added due to autoload fallback.');
        $this->assertFalse(
            $container->has(EngineInterface::class),
            'Container should not have EngineInterface before service provider added.'
        );

        $container->addProvider(CarDeferredProvider::class);

        $this->assertTrue(
            $container->has('car'),
            'Container should not have "car" after adding deferred provider.'
        );
        $this->assertTrue(
            $container->has(Car::class),
            'Container should have Car after adding deferred provider due to autoload fallback.'
        );
        $this->assertFalse(
            $container->has(EngineInterface::class),
            'Container should not have EngineInterface after adding deferred provider.'
        );

        $car = $container->get('car');
        $this->assertTrue(
            $container->has(EngineInterface::class),
            'CarDeferredProvider should have registered EngineInterface once "car" was requested from container.'
        );

        $engine = $container->get(EngineInterface::class);

        // ensure container return instances of classes register from provider
        $this->assertInstanceOf(Car::class, $car, 'Service provider should have set correct class for a "car".');
        $this->assertInstanceOf(
            EngineMarkOne::class,
            $engine,
            'Service provider should have set EngineInterface as an EngineMarkOne.'
        );

        // ensure get invoked DeferredServiceProviderInterface::register
        $this->assertTrue(
            $container->has('car'),
            'CarDeferredProvider should have registered "car" once "car" was requested from container.'
        );
    }
}
