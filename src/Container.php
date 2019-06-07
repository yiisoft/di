<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerInterface;
use SplObjectStorage;
use yii\di\contracts\DeferredServiceProvider;
use yii\di\contracts\Definition;
use yii\di\contracts\ServiceProvider;
use yii\di\definitions\ArrayDefinition;
use yii\di\definitions\Normalizer;
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
    private $definitions = [];
    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private $building = [];
    /**
     * @var contracts\DeferredServiceProvider[]|\SplObjectStorage list of providers
     * deferred to register till their services would be requested
     */
    private $deferredProviders;
    /**
     * @var Injector injector with this container.
     */
    private $injector;

    /**
     * @var object[]
     */
    private $instances;

    /** @var ?ContainerInterface */
    private $parentContainer;

    /**
     * Container constructor.
     *
     * @param array $definitions
     * @param ServiceProvider[] $providers
     *
     * @param ContainerInterface|null $parentContainer
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function __construct(
        array $definitions = [],
        array $providers = [],
        ?ContainerInterface $parentContainer = null
    ) {
        $this->parentContainer = $parentContainer;
        $this->setMultiple($definitions);
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
     * @param string|Reference $id the interface or an alias name that was previously registered via [[set()]].
     * @return object an instance of the requested interface.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    public function get($id, array $params = [])
    {
        $id = $this->getId($id);
        if (!isset($this->instances[$id])) {
            $object = $this->build($id, $params);
            $this->instances[$id] = $object;
            $this->initObject($object);
        }

        return $this->instances[$id];
    }

    public function getId($id): string
    {
        return is_string($id) ? $id : $id->getId();
    }

    /**
     * Returns normalized definition for a given id
     * @param string $id
     * @return Definition|null
     */
    public function getDefinition(string $id): ?Definition
    {
        return $this->definitions[$id] ?? null;
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param string $id the interface or an alias name that was previously registered via [[set()]].
     * @param array $params
     * @return object new built instance of the specified class.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException if there is nothing registered with alias or interface specified
     * @throws NotInstantiableException
     * @internal
     */
    protected function build(string $id, array $params = [])
    {
        if (isset($this->building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s',
                $id,
                implode(',', array_keys($this->building))
            ));
        }

        $this->building[$id] = 1;
        $this->registerProviderIfDeferredFor($id);
        $object = $this->buildInternal($id, $params);
        unset($this->building[$id]);

        return $object;
    }

    private function buildInternal(string $id, array $params = [])
    {
        if (!isset($this->definitions[$id])) {
            if ($this->parentContainer !== null) {
                return $this->parentContainer->get($id, $params);
            }
            return $this->buildPrimitive($id, $params);
        }

        return $this->definitions[$id]->resolve($this, $params);
    }

    private function buildPrimitive(string $class, array $params = [])
    {
        if (class_exists($class)) {
            $definition = ArrayDefinition::fromClassName($class);

            return $definition->resolve($this, $params);
        }

        throw new NotFoundException("No definition for $class");
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
     * @param string $id
     * @param mixed $definition
     * @throws InvalidConfigException
     * @see `Normalizer::normalize()`
     */
    public function set(string $id, $definition): void
    {
        $this->instances[$id] = null;
        $this->definitions[$id] = Normalizer::normalize($definition, $id);
    }

    /**
     * Sets multiple definitions at once.
     * @param array $config definitions indexed by their ids
     */
    public function setMultiple(array $config): void
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
    private function initObject($object)
    {
        if ($object instanceof Initiable) {
            $object->init();
        }

        return $object;
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * @param Definition[] $dependencies the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    public function resolveDependencies(array $dependencies): array
    {
        $result = [];
        /** @var Definition $dependency */
        foreach ($dependencies as $dependency) {
            $result[] = $this->resolve($dependency);
        }

        return $result;
    }

    /**
     * This function resolves a dependency recursively, checking for loops.
     * TODO add checking for loops
     * @param Definition $dependency
     * @return mixed
     */
    public function resolve(Definition $dependency)
    {
        while ($dependency instanceof Definition) {
            $dependency = $dependency->resolve($this->parentContainer ?? $this);
        }
        return $dependency;
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

        if ($provider instanceof DeferredServiceProvider) {
            $this->deferredProviders->attach($provider);
        } else {
            $provider->register($this);
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param string|array $providerDefinition class name or definition of provider.
     * @return ServiceProvider instance of service provider;
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function buildProvider($providerDefinition): ServiceProvider
    {
        $provider = Normalizer::normalize($providerDefinition)->resolve($this);
        if (!($provider instanceof ServiceProvider)) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProvider::class
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
     * Returns a value indicating whether the container has already instantiated
     * instance of the specified name.
     * @param string|Reference $id class name, interface name or alias name
     * @return bool whether the container has instance of class specified.
     */
    public function hasInstance($id): bool
    {
        $id = $this->getId($id);

        return isset($this->instances[$id]);
    }

    /**
     * Returns all instances set in container
     * @return array list of instance
     */
    public function getInstances() : array
    {
        return $this->instances;
    }
}
