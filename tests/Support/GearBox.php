<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * A gear box.
 */
class GearBox
{
    public function __construct(private int $maxGear = 5)
    {
    }
}
