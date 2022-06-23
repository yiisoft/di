<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * Produces cars
 */
class CarFactory
{
    public static function create(EngineInterface $engine): Car
    {
        return new Car($engine);
    }

    public function createByEngineName(EngineFactory $factory, $name): Car
    {
        return new Car($factory->createByName($name));
    }

    public function createWithColor(ColorInterface $color): Car
    {
        $car = new Car(EngineFactory::createDefault());

        return $car->setColor($color);
    }
}
