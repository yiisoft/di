<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\dependencies;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;
use yii\di\exceptions\InvalidConfigException;

/**
 * Reference points to a class name in the container
 */
class ClassDependency implements DependencyInterface
{
    private $class;

    private $optional;

    /**
     * Constructor.
     * @param string $class the class name
     */
    public function __construct(string $class, bool $optional)
    {
        $this->class = $class;
        $this->optional = $optional;
    }

    public function resolve(ContainerInterface $container)
    {
        try {
            $result = $container->get($this->class);
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
