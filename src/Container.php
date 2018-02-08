<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use SplObjectStorage;
use yii\di\contracts\DeferredServiceProviderInterface;
use yii\di\contracts\ServiceProviderInterface;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class Container implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $parent;
    /**
     * @var object[]
     */
    private $instances;
    /**
     * @var array object definitions indexed by their types
     */
    private $definitions;
    /**
     * @var ReflectionClass[] cached ReflectionClass objects indexed by class/interface names
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
     * @var contracts\DeferredServiceProviderInterface[]|\SplObjectStorage list of providers
     * deferred to register till their services would be requested
     */
    private $deferredProviders;

    /**
     * Container constructor.
     *
     * @param array $definitions
     * @param Container|null $parent
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    public function __construct(array $definitions = [], Container $parent = null)
    {
        if (isset($definitions['providers'])) {
            $providers = $definitions['providers'];
            unset($definitions['providers']);
        } else {
            $providers = [];
        }
        $this->definitions = $definitions;
        $this->parent = $parent;

        $this->deferredProviders = new SplObjectStorage();
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * Same instance of the class will be returned each time this method is called.
     *
     * @param string $id the interface name or an alias name (e.g. `foo`) that was previously registered via [[set()]].
     * @return object an instance of the requested interface.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException if there is nothing registered with alias or interface specified
     * @throws NotInstantiableException
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

        $this->registerProviderIfDeferredFor($id);

        if (!isset($this->definitions[$id])) {
            if ($this->parent !== null) {
                return $this->parent->get($id);
            }

            throw new NotFoundException("No definition for \"$id\" found");
        }

        $definition = $this->definitions[$id];

        if (is_string($definition)) {
            $definition = ['__class' => $definition];
        }

        if (is_array($definition) && !isset($definition[0], $definition[1])) {
            $object = $this->build($definition);
        } elseif (is_callable($definition)) {
            $object = $definition($this);
        } elseif (is_object($definition)) {
            $object = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        $this->instances[$id] = $object;

        unset($this->getting[$id]);

        return $object;
    }

    /**
     * Register providers from {@link deferredProviders} if they provide
     * definition for given identifier.
     *
     * @param string $id class or identifier of a service.
     */
    protected function registerProviderIfDeferredFor($id): void
    {
        $deferredProviders = $this->deferredProviders;
        if ($deferredProviders->count() === 0) {
            return;
        }

        foreach ($deferredProviders as $provider) {
            if ($provider->hasDefinitionFor($id)) {
                $provider->register();

                // provider should be removed after registration to not be registered again
                $deferredProviders->detach($provider);
            }
        }
    }

    /**
     * Sets a definition to the container. Definition may be defined multiple ways.
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
     * A callable array:
     *
     * ```php
     * $container->set('static_call', [MyClass::class, 'create']);
     * ```
     *
     * A definition array:
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
     * @param mixed $definition
     */
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
    public function setMultiple($config): void
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

        return isset($this->definitions[$id]);
    }

    /**
     * Creates an instance of the class definition with dependencies resolved
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

        if (isset($definition['__construct()'])) {
            foreach ($definition['__construct()'] as $index => $param) {
                $dependencies[$index] = $param;
            }
            unset($definition['__construct()']);
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        $object = $reflection->newInstanceArgs($dependencies);

        $definition = $this->resolveDependencies($definition);

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
                    throw new InvalidConfigException(
                        "Missing required parameter \"$name\" when instantiating \"$class\"."
                    );
                }
            }
        }

        return $dependencies;
    }

    /**
     * Adds service provider to the container. Unless service provider is deferred
     * it would be immediately registered.
     *
     * @param string|array $providerDefinition
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     *
     * @see ServiceProvider
     * @see DeferredServiceProvider
     */
    public function addProvider($providerDefinition): void
    {
        $provider = $this->buildProvider($providerDefinition);

        if ($provider instanceof DeferredServiceProviderInterface) {
            $this->deferredProviders->attach($provider);
        } else {
            $provider->register();
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param string|array $providerDefinition class name or definition of provider.
     * @return ServiceProviderInterface instance of service provider;
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    protected function buildProvider($providerDefinition)
    {
        if (is_string($providerDefinition)) {
            $provider = $this->build([
                '__class' => $providerDefinition,
                '__construct()' => [
                    $this,
                ]
            ]);
        } elseif (is_array($providerDefinition) && isset($providerDefinition['__class'])) {
            $providerDefinition['__construct()'] = [
                $this
            ];
            $provider = $this->build($providerDefinition);
        } else {
            throw new InvalidConfigException('Service provider definition should be a class name ' .
                'or array contains "__class" with a class name of provider.');
        }

        if (!($provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProviderInterface::class
            );
        }

        return $provider;
    }
}
