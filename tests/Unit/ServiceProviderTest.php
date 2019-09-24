<?php
namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Exceptions\NotInstantiableException;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarFactory;
use Yiisoft\Di\Tests\Support\CarProvider;

/**
 * Test for {@link Container} and {@link \Yiisoft\Di\support\ServiceProvider}
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 */
class ServiceProviderTest extends TestCase
{
    /**
     * @throws \Yiisoft\Factory\Exceptions\InvalidConfigException
     * @throws \Yiisoft\Factory\Exceptions\NotInstantiableException
     */
    public function testAddProviderByClassName(): void
    {
        $this->ensureProviderRegisterDefinitions(CarProvider::class);
    }

    /**
     * @throws \Yiisoft\Factory\Exceptions\InvalidConfigException
     * @throws \Yiisoft\Factory\Exceptions\NotInstantiableException
     */
    public function testAddProviderByDefinition(): void
    {
        $this->ensureProviderRegisterDefinitions([
            '__class' => CarProvider::class,
        ]);
    }

    /**
     * @throws \Yiisoft\Factory\Exceptions\InvalidConfigException
     * @throws \Yiisoft\Factory\Exceptions\NotInstantiableException
     */
    public function testAddProviderRejectDefinitionWithoutClass(): void
    {
        $this->expectException(NotInstantiableException::class);
        $container = new Container();
        $container->addProvider([
            'property' => 234
        ]);
    }

    /**
     * @param $provider
     *
     * @throws \Yiisoft\Factory\Exceptions\InvalidConfigException
     * @throws \Yiisoft\Factory\Exceptions\NotInstantiableException
     */
    protected function ensureProviderRegisterDefinitions($provider): void
    {
        $container = new Container();

        $this->assertFalse(
            $container->has(Car::class),
            'Container should not have Car registered before service provider added.'
        );
        $this->assertFalse(
            $container->has(CarFactory::class),
            'Container should not have CarFactory registered before service provider added.'
        );

        $container->addProvider($provider);

        // ensure addProvider invoked ServiceProviderInterface::register
        $this->assertTrue(
            $container->has(Car::class),
            'CarProvider should have registered Car once it was added to container.'
        );
        $this->assertTrue(
            $container->has(CarFactory::class),
            'CarProvider should have registered CarFactory once it was added to container.'
        );
    }
}
