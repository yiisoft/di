<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorSecondParamNotResolvable
{
    public function __construct(EngineMarkOne|EngineInterface $engine, string $name) {}
}
