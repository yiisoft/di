<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class UnionTypeInConstructorParamNotResolvable
{
    private EngineInterface|ColorInterface $param;

    public function __construct(EngineInterface|ColorInterface $param)
    {
        $this->param = $param;
    }
}
