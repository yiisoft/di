<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A garage
 */
final class Garage
{
    private SportCar $car;

    public function __construct(SportCar $car)
    {
        $this->car = $car;
    }

    public function getCar(): SportCar
    {
        return $this->car;
    }
}
