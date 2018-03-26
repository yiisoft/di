<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

/**
 * Factory is similar to the Container but creates new object every time.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
class Factory extends AbstractContainer
{
    public function create($id, array $config = [])
    {
        return $this->build($id, $config);
    }
}
