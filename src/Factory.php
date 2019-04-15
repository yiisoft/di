<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use yii\di\exceptions\InvalidConfigException;

class Factory extends Container implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($config, array $params = [])
    {
        $definition = Definition::normalize($config);
        return $definition->resolve($this);
    }

}
