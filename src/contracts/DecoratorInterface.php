<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\contracts;

/**
 * Represents decorator of objects fetched from the container.
 *
 * @see https://sourcemaking.com/design_patterns/decorator
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 * @since 1.0
 */
interface DecoratorInterface
{
    public function decorate($object): void;
}