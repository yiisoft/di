<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

/**
 * Initiable interface to mark classes needing `init()` after construction.
 * @deprecated Not recommended for use. Added only to support Yii 2.0 behavior.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
interface Initiable
{
    /**
     * Initializes the object.
     * This method is invoked after object created and configured.
     * @deprecated use constructor and getters/setters instead
     */
    public function init(): void;
}
