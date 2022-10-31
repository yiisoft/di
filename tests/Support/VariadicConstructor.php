<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class VariadicConstructor
{
    private array $parameters;

    public function __construct(private $first, private EngineInterface $engine, ...$parameters)
    {
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
