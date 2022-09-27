<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class OptionalConcreteDependency
{
    public function __construct(private ?\Yiisoft\Di\Tests\Support\Car $car = null)
    {
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }
}
