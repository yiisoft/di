<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class OptionalConcreteDependency
{
    private ?Car $car;

    public function __construct(Car $car = null)
    {
        $this->car = $car;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }
}
