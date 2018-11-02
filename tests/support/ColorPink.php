<?php

namespace yii\di\tests\support;

/**
 * Class ColorPink
 *
 * @package yii\di\tests\code
 */
class ColorPink implements ColorInterface
{
    private const COLOR_PINK = 'pink';

    /**
     * @return string
     */
    public function getColor(): string
    {
        return static::COLOR_PINK;
    }
}
