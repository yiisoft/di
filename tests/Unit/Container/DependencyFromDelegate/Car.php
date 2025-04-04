<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Container\DependencyFromDelegate;

final class Car
{
    public function __construct(
        public readonly EngineInterface $engine,
    )    {
    }
}
