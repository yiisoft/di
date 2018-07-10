<?php

namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use yii\di\Factory;
use yii\di\tests\code\EngineMarkOne;

/**
 * FactoryTest contains tests for \yii\di\Factory
 */
class FactoryTest extends TestCase
{
    public function testCreatesNewByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
        $two = $factory->create('engine');
        $this->assertFalse($one === $two);
    }

    public function testCreatesNewByClass()
    {
        $factory = new Factory();
        $one = $factory->create(EngineMarkOne::class);
        $two = $factory->create(EngineMarkOne::class);
        $this->assertFalse($one === $two);
    }
}
