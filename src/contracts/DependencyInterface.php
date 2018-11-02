<?php


namespace yii\di\contracts;


use yii\di\AbstractContainer;

/**
 * Interface DependencyInterface
 * @package yii\di\contracts
 */
interface DependencyInterface
{
    /**
     * @param AbstractContainer $container
     */
    public function resolve(AbstractContainer $container);
}