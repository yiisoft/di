<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class B
{
    public function __construct(public ?A $a = null) {}
}
