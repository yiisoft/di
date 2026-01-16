<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Container\DependencyFromDelegate;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;
use Yiisoft\Di\BuildingException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;
use function sprintf;

final class DependencyFromDelegateTest extends TestCase
{
    public function testAnotherContainer(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ContainerInterface::class => new SimpleContainer(),
                    Car::class => Car::class,
                ])
                ->withDelegates([
                    static fn() => new SimpleContainer([
                        Car::class => new Car(new Engine()),
                    ]),
                ]),
        );

        $car = $container->get(Car::class);

        assertInstanceOf(Car::class, $car);
        assertInstanceOf(Engine::class, $car->engine);
    }

    public function testNotFoundInDelegate(): void
    {
        $container = new Container(
            ContainerConfig::create()
                ->withDefinitions([
                    ContainerInterface::class => new SimpleContainer(),
                    'car' => Car::class,
                ])
                ->withDelegates([
                    static fn() => new Container(
                        ContainerConfig::create()
                            ->withDefinitions([
                                'car' => Car::class,
                            ]),
                    ),
                ]),
        );

        $exception = null;
        try {
            $container->get('car');
        } catch (Throwable $exception) {
        }

        assertInstanceOf(BuildingException::class, $exception);
        assertSame(
            sprintf(
                'Caught unhandled error "No definition or class found or resolvable for "%2$s" while building "%1$s" -> "%3$s" -> "%2$s"." while building "%1$s".',
                'car',
                EngineInterface::class,
                Car::class,
            ),
            $exception->getMessage(),
        );

        $previous = $exception->getPrevious();
        assertInstanceOf(NotFoundException::class, $previous);
        $this->assertSame(
            sprintf(
                'No definition or class found or resolvable for "%2$s" while building "%1$s" -> "%3$s" -> "%2$s".',
                'car',
                EngineInterface::class,
                Car::class,
            ),
            $previous->getMessage(),
        );
    }
}
