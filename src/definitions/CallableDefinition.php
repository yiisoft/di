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
        $callback = $this->method;
        return $callback($container, $params);
    }
}
