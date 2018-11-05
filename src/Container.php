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
use yii\di\contracts\DependencyInterface;
use yii\di\contracts\ServiceProviderInterface;
use yii\di\exceptions\CircularReferenceException;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotFoundException;
use yii\di\exceptions\NotInstantiableException;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
class Container implements ContainerInterface
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
     * @var object[]
     */
    private $instances;

    /** @var ?ContainerInterface */
    private $rootContainer;
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
        array $providers = [],
        ?ContainerInterface $rootContainer = null
    ) {
        $this->rootContainer = $rootContainer;
        $this->setAll($definitions);
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
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    public function get($id)
    {
        $reference = is_string($id) ? Reference::to($id) : $id;
        $id = $reference->getId();
        if (!isset($this->instances[$id])) {
            $object = $this->build($reference);
            $this->initObject($object);
            $this->instances[$id] = $object;
        }
        return $this->instances[$id];
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
        $id = $reference->getId();

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
        $this->instances[$id] = null;
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
        return isset($this->definitions[$id]);
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
            $dependency = $dependency->resolve($this->getRootContainer());
        }
        return $dependency;
    }

    public function getRootContainer(): ContainerInterface
    {
        return $this->rootContainer ?? $this;
    }

    /**
     * Returns a value indicating whether the container has already instantiated
     * instance of the specified name.
     * @param string $id class name, interface name or alias name
     * @return bool whether the container has instance of class specified.
     * @throws CircularReferenceException
     */
    public function hasInstance($id): bool
    {
        $reference = is_string($id) ? Reference::to($id) : $id;
        $id = $reference->getId();

        return isset($this->instances[$id]);
    }
}
