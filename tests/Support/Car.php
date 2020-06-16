<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A car
 */
class Car
{
    public ColorInterface $color;
    private EngineInterface $engine;

    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getEngineName(): string
    {
        return $this->engine->getName();
    }
}
