<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use SplObjectStorage;
use yii\di\contracts\DeferredServiceProviderInterface;
use yii\di\contracts\DependencyInterface;
use yii\di\contracts\ServiceProviderInterface;
use yii\di\dependencies\NamedDependency;
use yii\di\dependencies\ValueDependency;
use yii\di\exceptions\CircularReferenceException;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotFoundException;
use yii\di\exceptions\NotInstantiableException;
use yii\di\resolvers\ClassNameResolver;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
abstract class AbstractContainer
{
    /**
     * @var Definition[] object definitions indexed by their types
     */
    private $definitions;
    /**
     * @var ReflectionClass[] cached ReflectionClass objects indexed by class/interface names
     */
    private $reflections = [];
    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $dependencies = [];
    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private $building = [];
    /**
     * @var contracts\DeferredServiceProviderInterface[]|\SplObjectStorage list of providers
     * deferred to register till their services would be requested
     */
    private $deferredProviders;
    /**
     * @var Injector injector with this container.
     */
    protected $injector;

    /**
     * Container constructor.
     *
     * @param array $definitions
     * @param ServiceProviderInterface[] $providers
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function __construct(
        array $definitions = [],
        array $providers = []
    ) {
        $this->setAll($definitions);
        $this->deferredProviders = new SplObjectStorage();
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function get($id)
    {
        $reference = is_string($id) ? Reference::to($id) : $id;
        return $this->build($reference);
    }

    private function getReflectionClass(string $class)
    {
        if (!isset($this->reflections[$class])) {
            $this->reflections[$class] = new ReflectionClass($class);
        }

        return $this->reflections[$class];
    }
    /**
     * Returns normalized definition for a given id
     */
    public function getDefinition(string $id): ?Definition
    {
        return $this->definitions[$id] ?? null;
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param Reference $id the interface name or an alias name (e.g. `foo`) that was previously registered via [[set()]].
     * @param array $config
     * @return object new built instance of the specified class.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException if there is nothing registered with alias or interface specified
     * @throws NotInstantiableException
     * @internal
     */
    protected function build(Reference $reference)
    {
        $id = $this->dereference($reference);

        if (isset($this->building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s',
                $id,
                implode(',', array_keys($this->building))
            ));
        }
        $this->building[$id] = 1;

        $this->registerProviderIfDeferredFor($id);

        if (!isset($this->definitions[$id])) {
            throw new NotFoundException("No definition for $id");
        }


        $definition = $this->definitions[$id];
        return $definition->resolve($this);
    }

    /**
     * Creates new instance by given definition.
     *
     * @param array|string|object $definition
     * @return object new built instance
     * @throws InvalidConfigException when definition type is not expected
     * @throws NotInstantiableException
     */
    private function buildWithDefinition($definition)
    {
        if (\is_string($definition)) {
            $definition = ['__class' => $definition];
        }

        if (\is_array($definition) && !isset($definition[0], $definition[1])) {
            return $this->buildFromConfig($definition);
        }

        if (\is_callable($definition)) {
            return $definition($this);
        }

        if (\is_object($definition)) {
            return $definition;
        }

        throw new InvalidConfigException('Unexpected object definition type: ' . \gettype($definition));
    }

    /**
     * Register providers from {@link deferredProviders} if they provide
     * definition for given identifier.
     *
     * @param string $id class or identifier of a service.
     */
    private function registerProviderIfDeferredFor(string $id): void
    {
        $providers = $this->deferredProviders;

        foreach ($providers as $provider) {
            if ($provider->hasDefinitionFor($id)) {
                $provider->register($this);

                // provider should be removed after registration to not be registered again
                $providers->detach($provider);
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
        $this->definitions[$id] = Definition::normalize($definition);
    }

    /**
     * Sets multiple definitions at once.
     * @param array $config definitions indexed by their ids
     */
    public function setAll($config): void
    {
        foreach ($config as $id => $definition) {
            $this->set($id, $definition);
        }
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $id class name, interface name or alias name
     * @return bool whether the container is able to provide instance of class specified.
     * @throws CircularReferenceException
     * @see set()
     */
    public function has($id): bool
    {
        $id = $this->dereference(Reference::to($id));
        return isset($this->definitions[$id]);
    }

    private function internalDereference(Reference $reference, array $path)
    {
        $id = $reference->getId();

        if (isset($this->definitions[$id]) && $this->definitions[$id] instanceof Reference) {
            // Check path.
            if (isset($path[$id])) {
                throw new CircularReferenceException(sprintf(
                    'Circular reference to "%s" detected while dereferencing: %s; building: %s',
                    $id,
                    implode(',', array_keys($path)),
                    implode(',', array_keys($this->building))
                ));
            }
            $path[$id] = true;
            return $this->internalDereference($this->definitions[$id], $path);
        }

        return $id;
    }

    /**
     * Follows references recursively to find the deepest ID.
     *
     * @param Reference $id
     * @return string
     * @throws CircularReferenceException when circular reference gets detected
     * @internal
     */
    public function dereference(Reference $reference): string
    {
        return $this->internalDereference($reference, []);
    }

    /**
     * Creates an instance of the class definition with dependencies resolved
     * @param array $config
     * @return object the newly created instance of the specified class
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    protected function buildFromConfig(array $config)
    {
        if (empty($config['__class'])) {
            throw new NotInstantiableException(var_export($config, true));
        }
        $class = $config['__class'];
        unset($config['__class']);

        $dependencies = $this->getDependencies($class);


        if (isset($config['__construct()'])) {
            foreach (array_values($config['__construct()']) as $index => $param) {
                if (!$param instanceof Reference) {
                    $dependencies[$index] = new ValueDependency($param);
                } else {
                    $dependencies[$index] = new NamedDependency($this->dereference($param), false);
                }
            }
            unset($config['__construct()']);
        }

        $resolvedDependencies = $this->resolveDependencies($dependencies);
        $object = $this->getReflectionClass($class)->newInstanceArgs($resolvedDependencies);

        $this->configure($object, $config);
        return $object;
    }

    /**
     * Does after build object initialization.
     * At the moment only `init()` if class implements Initiable interface.
     *
     * @param object $object
     * @return object
     */
    protected function initObject($object)
    {
        if ($object instanceof Initiable) {
            $object->init();
        }

        return $object;
    }



    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return DependencyInterface[] the dependencies of the specified class.
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @internal
     */
    public function getDependencies($class): array
    {
        if (!isset($this->dependencies[$class])) {
            // For now use hard coded resolver.
            $resolver = new ClassNameResolver();

            $this->dependencies[$class] = $resolver->resolveConstructor($class);
        }

        return $this->dependencies[$class];
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * @param DependencyInterface[] $dependencies the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $result = [];
        /** @var DependencyInterface $dependency */
        foreach ($dependencies as $dependency) {
            $result[] = $this->resolve($dependency);
        }

        return $result;
    }

    /**
     * Adds service provider to the container. Unless service provider is deferred
     * it would be immediately registered.
     *
     * @param string|array $providerDefinition
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @see ServiceProvider
     * @see DeferredServiceProvider
     */
    public function addProvider($providerDefinition): void
    {
        $provider = $this->buildProvider($providerDefinition);

        if ($provider instanceof DeferredServiceProviderInterface) {
            $this->deferredProviders->attach($provider);
        } else {
            $provider->register($this);
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param string|array $providerDefinition class name or definition of provider.
     * @return ServiceProviderInterface instance of service provider;
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function buildProvider($providerDefinition): ServiceProviderInterface
    {
        $provider = Definition::normalize($providerDefinition)->resolve($this);
        if (!($provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProviderInterface::class
            );
        }

        return $provider;
    }

    /**
     * Returns injector.
     *
     * @return Injector
     */
    public function getInjector(): Injector
    {
        if ($this->injector === null) {
            $this->injector = new Injector($this);
        }

        return $this->injector;
    }

    /**
     * This function resolves a dependency recursively, checking for loops.
     * @param DependencyInterface $dependency
     * @return DependencyInterface
     */
    protected function resolve(DependencyInterface $dependency)
    {
        while ($dependency instanceof DependencyInterface) {
            $dependency = $dependency->resolve($this);
        }
        return $dependency;
    }

    /**
     * @param string $class The class name to construct
     * @param DependencyInterface[] $dependencies The list if dependencies
     * @internal
     */
    public function createFromDependencies(string $class, array $dependencies = [])
    {
        return $this->getReflectionClass($class)->newInstanceArgs($this->resolveDependencies($dependencies));
    }
}
