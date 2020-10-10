<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarProvider;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Factory\Exceptions\InvalidConfigException;

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
    }

    public function testAddProviderByDefinition(): void
    {
        $this->ensureProviderRegisterDefinitions([
            '__class' => CarProvider::class,
        ]);
    }

    public function testAddProviderRejectDefinitionWithoutClass(): void
    {
        $this->expectException(InvalidConfigException::class);
        $container = new Container([], [
            ['property' => 234]
        ]);
    }

    protected function ensureProviderRegisterDefinitions($provider): void
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
