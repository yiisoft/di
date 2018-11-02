<?php

namespace yii\di\tests\support;

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
