<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use yii\di\Factory;
use yii\di\Reference;
use yii\di\tests\support\EngineMarkOne;
use Psr\Container\ContainerInterface;

/**
 * FactoryTest contains tests for \yii\di\Factory
 * @skip
 */
class FactoryTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        #$this->markTestSkipped('Factory needs refactorying');
    }

    public function testCreateByAlias()
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
        $two = $factory->create('engine');
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
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

    public function testFactoryInContainer()
    {
        $factory = new Factory();
        $factory->setMultiple([
            'factory' => [
                '__class' => Factory::class,
                '__construct' => [
                    'definitions'   => [],
                    'providers'     => [],
                    'parent'        => Reference::to('container'),
                ],
            ],
            'container' => function (ContainerInterface $container) {
                return $container;
            },
        ]);
        $this->assertSame($factory, $factory->get('container'));
        $one = $factory->create('factory');
        $two = $factory->create('factory');
        $this->assertNotSame($one, $two);
        $this->assertNotSame($one, $factory);
        $this->assertInstanceOf(Factory::class, $one);
        $this->assertInstanceOf(Factory::class, $two);
    }
}
