<?php

namespace yii\di\tests\code;

/**
 * Interface ColorInterface defines car color
 *
 * @package yii\di\tests\code
 */
interface ColorInterface
{
    /**
     * @return string
     */
    public function getColor(): string;
}
