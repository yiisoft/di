<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use yii\di\ArrayContainerBuilder;
use yii\di\Container;
use yii\di\Injector;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkTwo;

/**
 * InjectorTest contains tests for \yii\di\Injector
 */
class InjectorTest extends TestCase
{
    public function testInvoke()
    {
        $container = (new ArrayContainerBuilder([
            EngineInterface::class => ['__class' => EngineMarkTwo::class],
        ]))->build();

        $getEngineName = function (EngineInterface $engine) {
            return $engine->getName();
        };

        $injector = new Injector($container);
        $engineName = $injector->invoke($getEngineName);

        $this->assertSame('Mark Two', $engineName);
    }
}
