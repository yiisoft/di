<?php
namespace yii\di\definitions;

use yii\di\Container;
use yii\di\contracts\Definition;
use yii\di\exceptions\InvalidConfigException;

/**
 * Reference points to a class name in the container
 */
class ClassDefinition implements Definition
{
    private $class;

    private $optional;

    /**
     * Constructor.
     * @param string $class the class name
     * @param bool $optional if null should be returned instead of throwing an exception
     */
    public function __construct(string $class, bool $optional)
    {
        $this->class = $class;
        $this->optional = $optional;
    }

    public function resolve(Container $container, array $params = [])
    {
        try {
            $result = $container->getWithParams($this->class, $params);
        } catch (\Throwable $t) {
            if ($this->optional) {
                return null;
            }
            throw $t;
        }

        if (!$result instanceof $this->class) {
            throw new InvalidConfigException('Container returned incorrect type for service ' . $this->class);
        }
        return $result;
    }
}
