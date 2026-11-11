<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
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
            'State resetter object should be instance of "' . StateResetter::class . '", "stdClass" given.',
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
    public function testNumericServiceIdDoesNotReplaceNestedResetter(): void
    {
        $service = new EngineMarkOne(0);
        $container = new SimpleContainer(['service' => $service, '0' => $service]);
        $nested = new StateResetter($container);
        $callback = function () {
            $this->number++;
        };
        $nested->setResetters(['service' => $callback]);
        $resetter = new StateResetter($container);
        $resetter->setResetters([$nested]);
        $resetter->addResetter('0', $callback);
        $resetter->reset();
        $this->assertSame(2, $service->getNumber());
    }

}
