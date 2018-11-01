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
 * Dependency points a service with a given class in the container which must implement a given class
 */
class NamedClassDependency implements DependencyInterface
{
    private $id;

    private $class;

    private $optional;

    /**
     * Constructor.
     * @param string $id the component ID
     */
    public function __construct(string $id, string $class, bool $optional)
    {
        $this->id = $id;
        $this->class = $class;
        $this->optional = $optional;
    }

    public static function to(string $id, string $class)
    {
        return new self($id, $class, false);
    }

    public function resolve(ContainerInterface $container)
    {
        try {
            $result = $container->get($this->id);
        } catch (\Throwable $t) {
            if ($this->optional) {
                return null;
            }
            throw $t;
        }

        if (!$result instanceof $this->class) {
            throw new InvalidConfigException(strtr('Container returned incorrect type for service {s}, expected {e}, got {r}', [
                '{s}' => $this->id,
                '{e}' => $this->class,
                '{r}' => get_class($result)
            ]));
        }
        return $result;
    }
}
