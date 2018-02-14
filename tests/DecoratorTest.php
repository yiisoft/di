<?php


namespace yii\di\tests;

use PHPUnit\Framework\TestCase;
use yii\di\Container;
use yii\di\contracts\DecoratorInterface;
use yii\di\InvalidConfigException;
use yii\di\tests\code\Car;
use yii\di\tests\code\CarColorDecorator;
use yii\di\tests\code\CarOwnerDecorator;
use yii\di\tests\code\EngineInterface;
use yii\di\tests\code\EngineMarkOne;

/**
 * Test for {@link \yii\di\contracts\DecoratorInterface}
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 */
class DecoratorTest extends TestCase
{
    private const EXPECTED_CAR_COLOR = 'black';
    private const EXPECTED_CAR_OWNER = 'Marcus Lom';

    public function testAddDecoratorAsClassName()
    {
        $container = $this->createContainer();
        $container->addDecorator(Car::class, CarColorDecorator::class);

        $car = $container->get(Car::class);

        $this->assertEquals(
            self::EXPECTED_CAR_COLOR,
            $car->color,
            'CarColorDecorator should have set car color'
        );
    }

    public function testAddDecoratorAsObject()
    {
        $container = $this->createContainer();
        $container->addDecorator(Car::class, new CarColorDecorator());

        $car = $container->get(Car::class);

        $this->assertEquals(
            self::EXPECTED_CAR_COLOR,
            $car->color,
            'CarColorDecorator should have set car color'
        );
    }

    public function testAddDecoratorAsCallable()
    {
        $container = $this->createContainer();
        $container->addDecorator(Car::class, function (Car $car) {
            $car->color = self::EXPECTED_CAR_COLOR;
        });

        $car = $container->get(Car::class);

        $this->assertEquals(
            self::EXPECTED_CAR_COLOR,
            $car->color,
            'callable decorator should have set car color'
        );
    }

    public function testAddDecoratorWithUnsupportedType()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Decorator should be a callable or implement ' . DecoratorInterface::class);

        $container = $this->createContainer();
        $container->addDecorator(Car::class, new EngineMarkOne());
    }

    public function testAddSeveralDecorators()
    {
        $container = $this->createContainer();
        $container->addDecorator(Car::class, CarColorDecorator::class);
        $container->addDecorator(Car::class, new CarOwnerDecorator());

        $car = $container->get(Car::class);

        $this->assertEquals(
            self::EXPECTED_CAR_COLOR,
            $car->color,
            'CarColorDecorator should have set car color'
        );
        $this->assertEquals(
            self::EXPECTED_CAR_OWNER,
            $car->owner,
            'CarOwnerDecorator should have set car owner'
        );
    }
    public function testAddSeveralDecoratorsThroughContainerDefinitions()
    {
        $container = new Container([
            Car::class => Car::class,
            EngineInterface::class => EngineMarkOne::class,
            'decorators' => [
                Car::class => [
                    CarColorDecorator::class,
                    new CarOwnerDecorator(),
                ]
            ]
        ]);

        $car = $container->get(Car::class);

        $this->assertEquals(
            self::EXPECTED_CAR_COLOR,
            $car->color,
            'CarColorDecorator should have set car color'
        );
        $this->assertEquals(
            self::EXPECTED_CAR_OWNER,
            $car->owner,
            'CarOwnerDecorator should have set car owner'
        );
    }

    protected function createContainer(): Container
    {
        return new Container([
            Car::class => Car::class,
            EngineInterface::class => EngineMarkOne::class,
        ]);
    }
}
