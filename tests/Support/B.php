<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

class B
{
    public ?A $a;

    public function __construct(?A $a)
    {
        $this->a = $a;
    }
}
