<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * Class ColorPink
 */
class ColorPink implements ColorInterface
{
    private const COLOR_PINK = 'pink';

    public function getColor(): string
    {
        return static::COLOR_PINK;
    }
}
