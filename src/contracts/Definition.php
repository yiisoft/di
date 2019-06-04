<?php
namespace yii\di\contracts;

use yii\di\Container;

/**
 * Interface DefinitionInterface
 * @package yii\di\contracts
 */
interface Definition
{
    /**
     * @param Container $container
     * @param array $params constructor params
     * @return mixed|object
     */
    public function resolve(Container $container, array $params = []);
}
