<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\dependencies;

use Psr\Container\ContainerInterface;
use yii\di\Container;
use yii\di\contracts\DependencyInterface;

/**
 * Reference points to a service name in the container
 */
class NamedDependency implements DependencyInterface
{
    private $id;

    private $optional;

    /**
     * Constructor.
     * @param string $id the component ID
     */
    public function __construct(string $id, bool $optional)
    {
        $this->id = $id;
        $this->optional = $optional;
    }

    public static function to(string $id)
    {
        return new self($id, false);
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
        return $result;
    }
}
