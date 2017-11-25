<?php

namespace yii\di;

use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
class Container implements ContainerInterface
{
    const TOKEN_CONSTRUCT = '__construct()';

    /**
     * @var self
     */
    private $parent;
    /**
     * @var object[]
     */
    private $objects;

    /**
     * @var array object definitions indexed by their types
     */
    private $definitions;
    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $reflections = [];

    /**
     * @var array
     */
    private $aliases;

    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $dependencies = [];

    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private $getting = [];


    /**
     * Container constructor.
     *
     * @param array $definitions
     * @param Container|null $parent
     */
    public function __construct(array $definitions = [], Container $parent = null)
    {
        $this->definitions = $definitions;
        $this->parent = $parent;
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * Same instance of the class will be returned each time this method is called.
     * @param string $id
     * @return object|void
     */
    public function get($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (!isset($this->objects[$id])) {
            if (!$definition = $this->getDefinition($id)) {
                throw new NotFoundException("No definition for \"$id\" found");
            }
            if (isset($this->getting[$id])) {
                throw new CircularReferenceException("Circular reference to \"$id\" detected.");
            }
            $this->getting[$id] = 1;
            $this->objects[$id] = $this->createObject($definition);

            unset($this->getting[$id]);
        }
        return $this->objects[$id];
    }

    /**
     * Creates the object definition
     * @param $definition
     * @return mixed|object
     * @throws InvalidConfigException
     */
    private function createObject($definition)
    {
        if (is_array($definition)) {
            if (isset($definition[0], $definition[1])) {
                $object = call_user_func([$definition[0], $definition[1]], $this);
            } else {
                $object = $this->build($definition);
            }
        } elseif ($definition instanceof \Closure) {
            $object = $definition($this);
        } elseif (is_object($definition)) {
            $object = $definition;
        }

        if (!isset($object)) {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        return $object;
    }

    /**
     * Gets the definition by ID
     * @param $id
     * @return array|callable|null|object
     */
    private function getDefinition($id)
    {
        if (!isset($this->definitions[$id])) {
            if ($this->parent !== null) {
                return $this->parent->get($id);
            }
            return null;
        }

        $definition = $this->definitions[$id];
        if (is_string($definition)) {
            $definition = ['__class' => $definition];
        }

        return $definition;
    }

    /**
     * Sets a defintion to the container. Defintion may be defined multiple ways.
     *
     * Interface name as string:
     *
     * ```php
     * $container->set('interface_name', EngineInterface::class);
     * ```
     *
     * A closure:
     *
     * ```php
     * $container->set('closure', function($container) {
     *     return new MyClass($container->get('db'));
     * });
     * ```
     *
     * Array-callable:
     *
     * ```php
     * $container->set('static_call', [MyClass::class, 'create']);
     * ```
     *
     * A defintion array:
     *
     * ```php
     * $container->set('full_definition', [
     *     '__class' => EngineMarkOne::class,
     *     '__construct()' => [42],
     *     'argName' => 'value',
     *     'setX()' => [42],
     * ]);
     * ```
     *
     * @param string $id
     * @param mixed $defintion
     */
    public function set(string $id, $defintion): void
    {
        $this->objects[$id] = null;

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }

        $this->definitions[$id] = $defintion;
    }

    /**
     * Sets multiple defintions at once
     * @param array $config defintions indexed by their ids
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
    public function setAlias(string $id, string $referenceId): void
    {
        $this->aliases[$id] = $referenceId;
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $id class name, interface name or alias name
     * @return bool whether the container is able to provide instance of id specified.
     * @see set()
     */
    public function has($id): bool
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return isset($this->objects[$id]) || isset($this->definitions[$id]);
    }

    /**
     * Creates an instance of the class defintion
     * @param array $definition
     * @return object the newly created instance of the specified class
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    protected function build(array $definition)
    {
        /* @var $reflection ReflectionClass */
        [$reflection, $dependencies] = $this->getDependencies($definition['__class']);
        unset($definition['__class']);

        if (isset($definition[self::TOKEN_CONSTRUCT])) {
            foreach ($definition[self::TOKEN_CONSTRUCT] as $index => $param) {
                $dependencies[$index] = $param;
            }
            unset($definition[self::TOKEN_CONSTRUCT]);
        }


        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        $object = $reflection->newInstanceArgs($dependencies);

        foreach ($definition as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                $object->$action = $arguments;
            }
        }

        return $object;
    }

    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class): array
    {

        if (isset($this->reflections[$class])) {
            return [$this->reflections[$class], $this->dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = new Reference($c === null ? null : $c->getName());
                }
            }
        }

        $this->reflections[$class] = $reflection;
        $this->dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * @param array $dependencies the dependencies
     * @param ReflectionClass $reflection the class reflection associated with the dependencies
     * @return array the resolved dependencies
     * @throws CircularReferenceException
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    protected function resolveDependencies($dependencies, $reflection = null): array
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Reference) {
                if ($dependency->getId() !== null) {
                    $dependencies[$index] = $this->get($dependency->getId());
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }

        return $dependencies;
    }
}
