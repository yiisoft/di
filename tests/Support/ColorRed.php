<?php


declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * Class ColorRed
 */
final class ColorRed implements ColorInterface
{
    private const COLOR_PINK = 'red';

    public function getColor(): string
    {
        return self::COLOR_PINK;
    }
}
