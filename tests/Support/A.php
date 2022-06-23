<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class A
{
    public ?B $b;

    public function __construct(?B $b = null)
    {
        $this->b = $b;
    }
}
