<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A sport car
 */
class SportCar
{
    public ColorInterface $color;

    public function __construct(
        private readonly EngineInterface $engine,
        private readonly int $maxSpeed,
    ) {}

    public function setColor(ColorInterface $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ColorInterface
    {
        return $this->color;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getEngineName(): string
    {
        return $this->engine->getName();
    }

    public function getMaxSpeed(): int
    {
        return $this->maxSpeed;
    }
}
