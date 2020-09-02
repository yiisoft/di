<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A car
 */
class Car implements CarInterface
{
    public ColorInterface $color;
    private EngineInterface $engine;
    private array $moreEngines;

    public function __construct(EngineInterface $engine, array $moreEngines = [])
    {
        $this->engine = $engine;
        $this->moreEngines = $moreEngines;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getEngineName(): string
    {
        return $this->engine->getName();
    }

    public function getMoreEngines(): array
    {
        return $this->moreEngines;
    }
}
