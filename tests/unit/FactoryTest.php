<?php

namespace yii\di\tests\unit;

use PHPUnit\Framework\TestCase;
use yii\di\Factory;
use yii\di\Reference;
use yii\di\tests\support\Car;
use yii\di\tests\support\EngineMarkOne;
use yii\di\tests\support\EngineMarkTwo;
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

    public function testCreateByAlias(): void
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->create('engine');
        $two = $factory->create('engine');
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testCreateByClass(): void
    {
        $factory = new Factory();
        $one = $factory->create(EngineMarkOne::class);
        $two = $factory->create(EngineMarkOne::class);
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByAlias(): void
    {
        $factory = new Factory();
        $factory->set('engine', EngineMarkOne::class);
        $one = $factory->get('engine');
        $two = $factory->get('engine');
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testGetByClass(): void
    {
        $factory = new Factory();
        $one = $factory->get(EngineMarkOne::class);
        $two = $factory->get(EngineMarkOne::class);
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one);
        $this->assertInstanceOf(EngineMarkOne::class, $two);
    }

    public function testFactoryInContainer(): void
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
            'container' => static function (ContainerInterface $container) {
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

    public function testCreateWithParams(): void
    {
        $factory = new Factory();
        $one = $factory->create(Car::class, [$factory->get(EngineMarkOne::class)]);
        $two = $factory->create(Car::class, [$factory->get(EngineMarkTwo::class)]);
        $this->assertNotSame($one, $two);
        $this->assertInstanceOf(Car::class, $one);
        $this->assertInstanceOf(Car::class, $two);
        $this->assertInstanceOf(EngineMarkOne::class, $one->getEngine());
        $this->assertInstanceOf(EngineMarkTwo::class, $two->getEngine());
    }
}
