<?php
namespace yii\di\definitions;

use yii\di\Container;
use yii\di\contracts\Definition;

class CallableDefinition implements Definition
{
    private $method;

    public function __construct(callable $method)
    {
        $this->method = $method;
    }

    /**
     * @param Container $container
     * @param array $params
     */
    public function resolve(Container $container, array $params = [])
    {
        return $container->getInjector()->invoke($this->method, $params);
    }
}
