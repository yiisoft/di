<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

final class ArgumentNameBinding
{
    public function __construct(
        public EngineInterface $markOne,
        public EngineInterface $markTwo,
    ) {
    }
}
