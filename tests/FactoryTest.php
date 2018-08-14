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

    /**
     * @depends testCreatesNewByAlias
     * @throws \yii\di\exceptions\CircularReferenceException
     * @throws \yii\di\exceptions\InvalidConfigException
     * @throws \yii\di\exceptions\NotFoundException
     * @throws \yii\di\exceptions\NotInstantiableException
     */
    public function testGet()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
        $one_got = $factory->get('engine');
        $this->assertNotNull($one_got);
        $this->assertInstanceOf(EngineMarkOne::class, $one_got);
    }
}
