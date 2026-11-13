<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class StateResetterTest extends TestCase
{
    public function testNonStateResetterObject(): void
    {
        $resetter = new StateResetter(new SimpleContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'State resetter should be an instance of "' . StateResetter::class . '" or a closure, "stdClass" given.',
        );
        $resetter->setResetters([
            new stdClass(),
        ]);
    }

    public function testStateResetterObjectForService(): void
    {
        $resetter = new StateResetter(new SimpleContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Callback for state resetter should be closure in format '
            . '`function (ContainerInterface $container): void`. '
            . 'Got "' . StateResetter::class . '".',
        );
        $resetter->setResetters([
            Car::class => $resetter,
        ]);
    }

    public function testResetNonObject(): void
    {
        $resetter = new StateResetter(
            new SimpleContainer([
                'value' => 42,
            ]),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches(
            '/^State resetter supports resetting objects only\. Container returned (integer|int)\.$/',
        );
        $resetter->setResetters([
            'value' => static function () {},
        ]);
    }

    public function testRawClosureResetter(): void
    {
        $container = new SimpleContainer();
        $resetter = new StateResetter($container);
        $passedContainer = null;
        $resetter->setResetters([
            static function (ContainerInterface $container) use (&$passedContainer): void {
                $passedContainer = $container;
            },
        ]);

        $resetter->reset();

        $this->assertSame($container, $passedContainer);
    }

    public function testNestedResetterWithRawClosure(): void
    {
        $service = new EngineMarkOne(0);
        $container = new SimpleContainer(['service' => $service]);
        $nested = new StateResetter($container);
        $nested->setResetters([
            'service' => function () {
                $this->number++;
            },
        ]);
        $resetter = new StateResetter($container);
        $resetter->setResetters([
            $nested,
            static function () use ($service): void {
                $service->setNumber($service->getNumber() + 10);
            },
        ]);

        $resetter->reset();

        $this->assertSame(11, $service->getNumber());
    }
}
