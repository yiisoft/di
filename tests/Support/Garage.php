<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A garage
 */
final class Garage
{
    public function __construct(private SportCar $car)
    {
    }

    public function getCar(): SportCar
    {
        return $this->car;
    }
}
