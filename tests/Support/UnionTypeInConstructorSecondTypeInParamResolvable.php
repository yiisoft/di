<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorSecondTypeInParamResolvable
{
    public function __construct(private readonly EngineInterface|EngineMarkOne $engine) {}
}
