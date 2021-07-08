<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\CarInterface;
use Yiisoft\Di\Tests\Support\EngineInterface;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\EngineMarkTwo;
use Yiisoft\Factory\Definitions\Reference;

abstract class AbstractModuleContainerTest extends TestCase
{
    abstract public function createModuleContainer(array $rootDefinitions, array $moduleDefinitions): array;

    public function testClassExistsIgnore(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            Car::class => [
                '__construct()' => [Reference::to(EngineMarkOne::class)]
            ],
        ], []);

        $car = $moduleContainer->get(Car::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());
    }

    public function testRootInterfaceDependencyInjection(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            CarInterface::class => Car::class,
        ]);

        $car = $moduleContainer->get(CarInterface::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkOne::class, $car->getEngine());
    }

    public function testOverrideRootInterfaceDependencyInjection(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            CarInterface::class => Car::class,
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $car = $moduleContainer->get(CarInterface::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkTwo::class, $car->getEngine());
    }

    public function testInterfaceOverride(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $engine = $moduleContainer->get(EngineInterface::class);

        $this->assertInstanceOf(EngineMarkTwo::class, $engine);
    }

    public function testInterfaceOverrideWithExistedInstance(): void
    {
        [$rootContainer, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $engineInRoot = $rootContainer->get(EngineInterface::class);
        $engine = $moduleContainer->get(EngineInterface::class);

        $this->assertInstanceOf(EngineMarkTwo::class, $engine);
        $this->assertInstanceOf(EngineMarkOne::class, $engineInRoot);
    }

    public function testInterfaceDependencyOverride(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $car = $moduleContainer->get(Car::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkTwo::class, $car->getEngine());
    }

    public function testInterfaceDependencyOverrideWithExistedInstance(): void
    {
        [$rootContainer, $moduleContainer] = $this->createModuleContainer([
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $carInRoot = $rootContainer->get(Car::class);
        $car = $moduleContainer->get(Car::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkTwo::class, $car->getEngine());
        $this->assertInstanceOf(Car::class, $carInRoot);
        $this->assertInstanceOf(EngineMarkOne::class, $carInRoot->getEngine());
    }

    public function testInterfaceDependencyFromInterfaceOverride(): void
    {
        [, $moduleContainer] = $this->createModuleContainer([
            CarInterface::class => Car::class,
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $car = $moduleContainer->get(CarInterface::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkTwo::class, $car->getEngine());
    }

    public function testInterfaceDependencyFromInterfaceOverrideWithExistedInstance(): void
    {
        [$rootContainer, $moduleContainer] = $this->createModuleContainer([
            CarInterface::class => Car::class,
            EngineInterface::class => EngineMarkOne::class,
        ], [
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $carInRoot = $rootContainer->get(CarInterface::class);
        $car = $moduleContainer->get(CarInterface::class);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(EngineMarkTwo::class, $car->getEngine());
        $this->assertInstanceOf(Car::class, $carInRoot);
        $this->assertInstanceOf(EngineMarkOne::class, $carInRoot->getEngine());
    }
}
