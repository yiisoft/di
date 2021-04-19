<?php


declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * Class ColorRed
 */
class ColorRed implements ColorInterface
{
    private const COLOR_PINK = 'red';

    public function getColor(): string
    {
        return static::COLOR_PINK;
    }
}

