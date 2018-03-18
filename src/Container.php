<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class Container extends AbstractContainer
{
    /**
     * @var object[]
     */
    private $instances;

    public function set(string $id, $definition): void
    {
        /// XXX don't get it
        /// is it intended to get different instances for same id?
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
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $object = $this->create($id);

        $this->instances[$id] = $object;

        return $object;
    }
}
