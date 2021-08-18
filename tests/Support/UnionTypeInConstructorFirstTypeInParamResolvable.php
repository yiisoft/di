<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorFirstTypeInParamResolvable
{
    private EngineMarkOne|EngineInterface $engine;

    public function __construct(EngineMarkOne|EngineInterface $engine)
    {
        $this->engine = $engine;
    }
}
