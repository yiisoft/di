<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorFirst
{
    private EngineInterface|EngineMarkOne $engine;

    public function __construct(EngineInterface|EngineMarkOne $engine)
    {
        $this->engine = $engine;
    }
}
