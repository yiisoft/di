<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorParamNotResolvable
{
    public function __construct(private readonly EngineInterface|ColorInterface $param)
    {
    }
}
