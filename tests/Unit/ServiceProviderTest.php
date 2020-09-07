<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Factory\Exceptions\NotInstantiableException;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarProvider;

/**
 * Test for {@link Container} and {@link \Yiisoft\Di\support\ServiceProvider}
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 */
class ServiceProviderTest extends TestCase
{
    public function testAddProvider(): void
    {
        $this->ensureProviderRegistered(new CarProvider);
    }

    protected function ensureProviderRegistered($provider): void
    {
        $container = new Container();

        $this->assertTrue(
            $container->has(Car::class),
            'Container should have Car registered before service provider added due to autoload fallback.'
        );
        $this->assertFalse(
            $container->has('car'),
            'Container should not have "car" registered before service provider added.'
        );
        $this->assertFalse(
            $container->has(EngineInterface::class),
            'Container should not have EngineInterface registered before service provider added.'
        );

        $container= new Container([], [$provider]);

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
}
