<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A gear box.
 */
class GearBox
{
    private int $maxGear;

    public function __construct(int $maxGear = 5)
    {
        $this->maxGear = $maxGear;
    }
}
