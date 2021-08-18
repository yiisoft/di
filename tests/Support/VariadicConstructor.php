<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class VariadicConstructor
{
    private $first;
    private EngineInterface $engine;
    private array $parameters;

    public function __construct($first, EngineInterface $engine, ...$parameters)
    {
        $this->first = $first;
        $this->engine = $engine;
        $this->parameters = $parameters;
    }

    public function getFirst()
    {
        return $this->first;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
