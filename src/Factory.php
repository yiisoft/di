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
    /**
     * Creates new instance by interface or class name or an alias.
     *
     * @param string $id class/interface name or an alias
     * @param array $config
     * @param array $params constructor parameters
     * @return object new built instance of the specified class
     */
    public function create($id, array $config = [], array $params = [])
    {
        if (!empty($params)) {
            $config['__construct()'] = array_merge($config['__construct()'] ?? [], $params);
        }

        return $this->build($id, $config);
    }
}
