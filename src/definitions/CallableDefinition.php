<?php


namespace yii\di\definitions;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DefinitionInterface;

class CallableDefinition implements DefinitionInterface
{
    private $method;

    public function __construct(callable $method)
    {
        $this->method = $method;
    }

    /**
     * @param ContainerInterface $container
     * @param array $params
     */
    public function resolve(ContainerInterface $container, array $params = [])
    {
        return $container->getInjector()->invoke($this->method, $params);
    }
}
