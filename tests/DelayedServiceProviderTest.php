<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use yii\di\Container;
use yii\di\tests\code\Car;
use yii\di\tests\code\CarDelayedProvider;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkOne;

/**
 * Test for {@link \yii\di\support\DelayedServiceProvider}
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 */
class DelayedServiceProviderTest extends TestCase
{
    public function testServiceProviderDelay()
    {
        $container = new Container();

        $this->assertFalse($container->has(Car::class), 'Container should not have Car before service provider added.');
        $this->assertFalse($container->has(EngineInterface::class), 'Container should not have EngineInterface before service provider added.');

        $container->addProvider(CarDelayedProvider::class);

        $this->assertFalse($container->has(Car::class), 'Container should not have Car after adding delayed provider.');
        $this->assertFalse($container->has(EngineInterface::class), 'Container should not have EngineInterface after adding delayed provider.');

        $car = $container->get(Car::class);
        $engine = $container->get(EngineInterface::class);

        // ensure container return instances of classes register from provider
        $this->assertInstanceOf(Car::class, $car, 'Service provider should have set correct class for a Car.');
        $this->assertInstanceOf(EngineMarkOne::class, $engine, 'Service provider should have set EngineInterface as an EngineMarkOne.');

        // ensure get invoked DelayedServiceProviderInterface::register
        $this->assertTrue($container->has(Car::class), 'CarDelayedProvider should have registered Car once Car was requested from container.');
        $this->assertTrue($container->has(EngineInterface::class), 'CarDelayedProvider should have registered EngineInterface once Car was requested from container.');
    }
}