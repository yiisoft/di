<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorFirstTypeInParamResolvable
{
    public function __construct(private readonly EngineMarkOne|EngineInterface $engine) {}
    {
    }

    public function getEngine(): EngineMarkOne|EngineInterface
    {
        return $this->engine;
    }
}
