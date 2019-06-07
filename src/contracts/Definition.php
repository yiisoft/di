<?php
namespace yii\di\contracts;

use Psr\Container\ContainerInterface;
use yii\di\Container;

/**
 * Interface DefinitionInterface
 * @package yii\di\contracts
 */
interface Definition
{
    /**
     * @param ContainerInterface $container
     * @param array $params constructor params
     * @return mixed|object
     */
    public function resolve(ContainerInterface $container, array $params = []);
}
