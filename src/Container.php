<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\DeferredServiceProviderInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Factory\Definitions\DynamicReference;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Factory\Exceptions\CircularReferenceException;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Exceptions\NotFoundException;
use Yiisoft\Factory\Exceptions\NotInstantiableException;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Definitions\ArrayDefinition;
use Yiisoft\Router\UrlMatcherInterface;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
final class Container extends AbstractContainerConfigurator implements ContainerInterface
{
    /**
     * @var array object definitions indexed by their types
     */
    private array $definitions = [];
    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private array $building = [];

    /**
     * @var object[]
     */
    private array $instances = [];

    private ?ContainerInterface $rootContainer = null;

    /**
     * Container constructor.
     *
     * @param array $definitions Definitions to put into container.
     * @param ServiceProviderInterface[]|string[] $providers Service providers to get definitions from.
     *
     * @param ContainerInterface|null $rootContainer Root container to delegate lookup to in case definition
     * is not found in current container.
     * @throws InvalidConfigException
     */
    public function __construct(
        array $definitions = [],
        array $providers = [],
        ContainerInterface $rootContainer = null
    ) {
        $this->setMultiple($definitions);
        $this->addProviders($providers);
        if ($rootContainer !== null) {
            $this->delegateLookup($rootContainer);
        }
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $id class name, interface name or alias name
     * @return bool whether the container is able to provide instance of class specified.
     * @see set()
     */
    public function has($id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * Same instance of the class will be returned each time this method is called.
     *
     * @param string|Reference $id The interface or an alias name that was previously registered.
     * @return object An instance of the requested interface.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            $this->instances[$id] = $this->build($id);
        }

        $instance = $this->instances[$id];

        return $instance;
    }

    /**
     * Delegate service lookup to another container.
     * @param ContainerInterface $container
     */
    protected function delegateLookup(ContainerInterface $container): void
    {
        if ($this->rootContainer === null) {
            $this->rootContainer = new CompositeContainer();
        }

        $this->rootContainer->attach($container);
    }

    /**
     * Sets a definition to the container. Definition may be defined multiple ways.
     * @param string $id
     * @param mixed $definition
     * @throws InvalidConfigException
     * @see `Normalizer::normalize()`
     */
    protected function set(string $id, $definition): void
    {
        $this->validateDefinition($definition);
        $this->instances[$id] = null;
        $this->definitions[$id] = $definition;
    }

    /**
     * Sets multiple definitions at once.
     * @param array $config definitions indexed by their ids
     * @throws InvalidConfigException
     */
    protected function setMultiple(array $config): void
    {
        foreach ($config as $id => $definition) {
            $this->set((string)$id, $definition);
        }
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param string $id The interface or an alias name that was previously registered.
     * @return object New built instance of the specified class.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @internal
     */
    private function build(string $id)
    {
        if (isset($this->building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s',
                $id,
                implode(',', array_keys($this->building))
            ));
        }

        $this->building[$id] = 1;
        $object = $this->buildInternal($id);
        unset($this->building[$id]);

        return $object;
    }

    private function processDefinition($definition): void
    {
        if ($definition instanceof DeferredServiceProviderInterface) {
            $definitions = $definition->getDefinitions();
            foreach ($definitions as $id => $def) {
                $this->set($id, $def);
            }
        }
    }

    private function validateDefinition($definition): void
    {
        if ($definition instanceof Reference || $definition instanceof DynamicReference) {
            return;
        }

        if (\is_string($definition)) {
            return;
        }

        if (\is_callable($definition)) {
            return;
        }

        if (\is_array($definition)) {
            return;
        }

        if (\is_object($definition)) {
            return;
        }

        throw new InvalidConfigException('Invalid definition:' . var_export($definition, true));
    }

    /**
     * @param string $id
     *
     * @return mixed|object
     * @throws InvalidConfigException
     * @throws NotFoundException
     */
    private function buildInternal(string $id)
    {
        if (!isset($this->definitions[$id])) {
            return $this->buildPrimitive($id);
        }
        $this->processDefinition($this->definitions[$id]);
        $definition = Normalizer::normalize($this->definitions[$id], $id);

        return $definition->resolve($this->rootContainer ?? $this);
    }

    /**
     * @param string $class
     *
     * @return mixed|object
     * @throws InvalidConfigException
     * @throws NotFoundException
     */
    private function buildPrimitive(string $class)
    {
        if (class_exists($class)) {
            $definition = new ArrayDefinition($class);

            return $definition->resolve($this->rootContainer ?? $this);
        }

        throw new NotFoundException("No definition for $class");
    }

    private function addProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Adds service provider to the container. Unless service provider is deferred
     * it would be immediately registered.
     *
     * @param string|array $providerDefinition
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @see ServiceProviderInterface
     * @see DeferredServiceProviderInterface
     */
    private function addProvider($providerDefinition): void
    {
        $provider = $this->buildProvider($providerDefinition);

        if ($provider instanceof DeferredServiceProviderInterface) {
            foreach ($provider->provides() as $id) {
                $this->definitions[$id] = $provider;
            }
        } else {
            $definitions = $provider->getDefinitions();
            foreach ($definitions as $id => $definition) {
                $this->set($id, $definition);
            }
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param string|array $providerDefinition class name or definition of provider.
     * @return ServiceProviderInterface instance of service provider;
     *
     * @throws InvalidConfigException
     */
    private function buildProvider($providerDefinition): ServiceProviderInterface
    {
        $provider = Normalizer::normalize($providerDefinition)->resolve($this);
        if (!($provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                'Service provider should be an instance of ' . ServiceProviderInterface::class
            );
        }

        return $provider;
    }
}
