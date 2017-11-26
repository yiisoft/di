<?php
namespace yii\di;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $parent;

    /**
     * @var callable[]
     */
    private $definitions;

    /**
     * @var array
     */
    private $aliases;

    private $instances = [];

    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private $getting = [];

    public function __construct($defintions = [], $parent = null)
    {
        $this->definitions = $defintions;
        $this->parent = $parent;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->getting[$id])) {
            throw new CircularReferenceException("Circular reference to \"$id\" detected.");
        }
        $this->getting[$id] = 1;

        if (!isset($this->definitions[$id])) {
            if ($this->parent !== null) {
                return $this->parent->get($id);
            }

            throw new NotFoundException("No definition for \"$id\" found");
        }

        $definition = $this->definitions[$id];

        if (is_callable($definition)) {
            $object = $definition($this);
            $this->instances[$id] = $object;
            unset($this->getting[$id]);
            return $object;
        }

        unset($this->getting[$id]);
        return $definition;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return isset($this->definitions[$id]);
    }

    public function set(string $id, $definition): void
    {
        $this->instances[$id] = null;

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }

        $this->definitions[$id] = $definition;
    }

    /**
     * Sets multiple definitions at once
     * @param array $config definitions indexed by their ids
     */
    public function configure($config): void
    {
        foreach ($config as $id => $definition) {
            $this->set($id, $definition);
        }
    }

    /**
     * Setting an alias so getting an object from container using $id results
     * in the same object as using $referenceId
     *
     * @param string $id
     * @param string $referenceId
     */
    public function setAlias(string $id, string $referenceId)
    {
        $this->aliases[$id] = $referenceId;
    }
}
