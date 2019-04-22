<?php


namespace yii\di\contracts;

use Psr\Container\ContainerInterface;

/**
 * Interface DefinitionInterface
 * @package yii\di\contracts
 */
interface DefinitionInterface
{
    /**
     * @param ContainerInterface $container
     * @param array $params constructor params
     * @return mixed|object
     */
    public function resolve(ContainerInterface $container, array $params = []);
}
