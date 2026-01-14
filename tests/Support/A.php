<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class A
{
    public function __construct(public ?B $b = null) {}
}
