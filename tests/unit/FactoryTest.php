<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use yii\di\Factory;
use yii\di\tests\support\EngineMarkOne;

/**
 * FactoryTest contains tests for \yii\di\Factory
 * @skip
 */
class FactoryTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Factory needs refactorying');
    }

    public function testCreateByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
//        $two = $factory->create('engine');
//        $this->assertNotSame($one, $two);
//        $this->assertInstanceOf(EngineMarkOne::class, $one);
//        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testCreateByClass()
    {
        $factory = new Factory();
        $one = $factory->create(EngineMarkOne::class);
        $two = $factory->create(EngineMarkOne::class);
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->get('engine');
        $two = $factory->get('engine');
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByClass()
    {
        $factory = new Factory();
        $one = $factory->get(EngineMarkOne::class);
        $two = $factory->get(EngineMarkOne::class);
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }
}
