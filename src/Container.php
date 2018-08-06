<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerInterface;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class Container extends AbstractContainer implements ContainerInterface
{
    /**
     * @var object[]
     */
    private $instances;

    public function set(string $id, $definition): void
    {
        $this->instances[$id] = null;

        parent::set($id, $definition);
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * Same instance of the class will be returned each time this method is called.
     *
     * @param string $id the interface name or an alias name (e.g. `foo`) that was previously registered via [[set()]].
     * @return object an instance of the requested interface.
     */
    public function get($id)
    {
        $id = $this->dereference($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $object = $this->build($id);
        $this->instances[$id] = $object;

        return $this->initObject($object);
    }

    /**
     * Returns a value indicating whether the container has already instantiated
     * instance of the specified name.
     * @param string $id class name, interface name or alias name
     * @return bool whether the container has instance of id specified.
     */
    public function hasInstance($id): bool
    {
        $id = $this->dereference($id);

        return isset($this->instances[$id]);
    }
}
