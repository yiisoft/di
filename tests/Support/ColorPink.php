<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * Class ColorPink
 */
final class ColorPink implements ColorInterface
{
    private const COLOR_PINK = 'pink';

    public function getColor(): string
    {
        return self::COLOR_PINK;
    }
}
