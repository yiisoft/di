<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class UnionTypeInConstructorFour
{
    public function __construct(EngineMarkOne|EngineInterface $engine, string $name)
    {
    }
}
