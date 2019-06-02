<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use yii\di\Container;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotFoundException;
use yii\di\Injector;
use yii\di\tests\support\ColorInterface;
use yii\di\tests\support\EngineInterface;
use yii\di\tests\support\EngineMarkTwo;

/**
 * InjectorTest contains tests for \yii\di\Injector
 */
class InjectorTest extends TestCase
{
    public function testInvoke()
    {
        $container = new Container([
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $getEngineName = static function (EngineInterface $engine) {
            return $engine->getName();
        };

        $injector = new Injector($container);
        $engineName = $injector->invoke($getEngineName);

        $this->assertSame('Mark Two', $engineName);
    }

    public function testMissingRequiredParameter()
    {
        $container = new Container([
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $getEngineName = static function (EngineInterface $engine, $two) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $this->expectException(InvalidConfigException::class);
        $engineName = $injector->invoke($getEngineName);
    }

    public function testNotFoundException()
    {
        $container = new Container([
            EngineInterface::class => EngineMarkTwo::class,
        ]);

        $getEngineName = static function (EngineInterface $engine, ColorInterface $color) {
            return $engine->getName();
        };

        $injector = new Injector($container);

        $this->expectException(NotFoundException::class);
        $engineName = $injector->invoke($getEngineName);
    }
}
