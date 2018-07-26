<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

/**
 * Initable interface.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
interface Initable
{
    /**
     * Initializes the object.
     * This method is invoked after object created and configured.
     */
    public function init();
}
