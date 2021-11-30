<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Di\StateResetter;
use Yiisoft\Di\Tests\Support\Car;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class StateResetterTest extends TestCase
{
    public function testNonStateResetterObject(): void
    {
        $resetter = new StateResetter(new SimpleContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'State resetter object should be instance of "' . StateResetter::class . '", "stdClass" given.'
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
            'Callback for state resetter should be closure in format ' .
            '`function (ContainerInterface $container): void`. ' .
            'Got "' . StateResetter::class . '".'
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
            ])
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'State resetter supports resetting objects only. Container returned integer.'
        );
        $resetter->setResetters([
            'value' => static function () {
            },
        ]);
    }
}
