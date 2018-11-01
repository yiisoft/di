<?php


namespace yii\di\contracts;


use Psr\Container\ContainerInterface;

/**
 * Interface DependencyInterface
 * @package yii\di\contracts
 */
interface DependencyInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function resolve(ContainerInterface $container);
}