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
    public function testCreateByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
        $two = $factory->create('engine');
        $this->assertFalse($one === $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testCreateByClass()
    {
        $factory = new Factory();
        $one = $factory->create(EngineMarkOne::class);
        $two = $factory->create(EngineMarkOne::class);
        $this->assertFalse($one === $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->get('engine');
        $two = $factory->get('engine');
        $this->assertFalse($one === $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByClass()
    {
        $factory = new Factory();
        $one = $factory->get(EngineMarkOne::class);
        $two = $factory->get(EngineMarkOne::class);
        $this->assertFalse($one === $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }
}
