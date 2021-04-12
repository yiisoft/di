<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\DeferredServiceProviderInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Factory\Definitions\ArrayDefinition;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Exceptions\CircularReferenceException;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Exceptions\NotFoundException;
use Yiisoft\Factory\Exceptions\NotInstantiableException;
use Yiisoft\Injector\Injector;

use function array_key_exists;
use function array_keys;
use function assert;
use function class_exists;
use function get_class;
use function implode;
use function is_object;
use function is_string;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
final class Container extends AbstractContainerConfigurator implements ContainerInterface
{
    private const ALLOWED_META = [];

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
    private ?CompositeContainer $rootContainer = null;

    /**
     * Container constructor.
     *
     * @param array $definitions Definitions to put into container.
     * @param ServiceProviderInterface[]|string[] $providers Service providers
     * to get definitions from.
     * @param ContainerInterface|null $rootContainer Root container to delegate
     * lookup to when resolving dependencies. If provided the current container
     * is no longer queried for dependencies.
     *
     * @throws InvalidConfigException
     */
    public function __construct(
        array $definitions = [],
        array $providers = [],
        ContainerInterface $rootContainer = null
    ) {
        $this->delegateLookup($rootContainer);
        $this->setDefaultDefinitions();
        $this->setMultiple($definitions);
        $this->addProviders($providers);

        // Prevent circular reference to ContainerInterface
        $this->get(ContainerInterface::class);
    }

    private function setDefaultDefinitions(): void
    {
        $container = $this->rootContainer ?? $this;
        $this->setMultiple([
            ContainerInterface::class => $container,
            Injector::class => new Injector($container),
        ]);
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     *
     * @param string $id class name, interface name or alias name
     *
     * @return bool whether the container is able to provide instance of class specified.
     *
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
     * @param string $id The interface or an alias name that was previously registered.
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     *
     * @return mixed|object An instance of the requested interface.
     *
     * @psalm-template T
     * @psalm-param string|class-string<T> $id
     * @psalm-return ($id is class-string ? T : mixed)
     */
    public function get($id)
    {
        if (!array_key_exists($id, $this->instances)) {
            $this->instances[$id] = $this->build($id);
        }

        return $this->instances[$id];
    }

    /**
     * Delegate service lookup to another container.
     *
     * @param ContainerInterface $container
     */
    protected function delegateLookup(?ContainerInterface $container): void
    {
        if ($container === null) {
            return;
        }
        if ($this->rootContainer === null) {
            $this->rootContainer = new CompositeContainer();
            $this->setDefaultDefinitions();
        }

        $this->rootContainer->attach($container);
    }

    /**
     * Sets a definition to the container. Definition may be defined multiple ways.
     *
     * @param string $id
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     *
     * @see `Normalizer::normalize()`
     */
    protected function set(string $id, $definition): void
    {
        Normalizer::validate($definition);
        unset($this->instances[$id]);
        $this->definitions[$id] = $definition;
    }

    /**
     * Sets multiple definitions at once.
     *
     * @param array $config definitions indexed by their ids
     *
     * @throws InvalidConfigException
     */
    protected function setMultiple(array $config): void
    {
        foreach ($config as $id => $definition) {
            if (!is_string($id)) {
                throw new InvalidConfigException(sprintf('Key must be a string. %s given.', $this->getVariableType($id)));
            }
            $this->set($id, $definition);
        }
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param string $id The interface or an alias name that was previously registered.
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     *
     * @return mixed|object New built instance of the specified class.
     *
     * @internal
     */
    private function build(string $id)
    {
        if (isset($this->building[$id])) {
            if ($id === ContainerInterface::class) {
                return $this;
            }
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(',', array_keys($this->building))
            ));
        }

        $this->building[$id] = 1;
        try {
            $object = $this->buildInternal($id);
        } finally {
            unset($this->building[$id]);
        }

        return $object;
    }

    /**
     * @param mixed $definition
     */
    private function processDefinition($definition): void
    {
        if ($definition instanceof DeferredServiceProviderInterface) {
            $definition->register($this);
        }
    }

    /**
     * @param string $id
     *
     * @throws InvalidConfigException
     * @throws NotFoundException
     *
     * @return mixed|object
     */
    private function buildInternal(string $id)
    {
        if (!isset($this->definitions[$id])) {
            return $this->buildPrimitive($id);
        }
        $this->processDefinition($this->definitions[$id]);
        $definition = Normalizer::normalize($this->definitions[$id], $id, [], self::ALLOWED_META);

        return $definition->resolve($this->rootContainer ?? $this);
    }

    /**
     * @param string $class
     *
     * @throws InvalidConfigException
     * @throws NotFoundException
     *
     * @return mixed|object
     */
    private function buildPrimitive(string $class)
    {
        if (class_exists($class)) {
            $definition = new ArrayDefinition([ArrayDefinition::CLASS_NAME => $class]);

            return $definition->resolve($this->rootContainer ?? $this);
        }

        throw new NotFoundException($class);
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
     * @param mixed $providerDefinition
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     *
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
            $provider->register($this);
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param mixed $providerDefinition class name or definition of provider.
     *
     * @throws InvalidConfigException
     *
     * @return ServiceProviderInterface instance of service provider;
     */
    private function buildProvider($providerDefinition): ServiceProviderInterface
    {
        $provider = Normalizer::normalize($providerDefinition)->resolve($this);
        assert($provider instanceof ServiceProviderInterface, new InvalidConfigException(
            sprintf(
                'Service provider should be an instance of %s. %s given.',
                ServiceProviderInterface::class,
                $this->getVariableType($provider)
            )
        ));

        return $provider;
    }

    /**
     * @param mixed $variable
     */
    private function getVariableType($variable): string
    {
        if (is_object($variable)) {
            return get_class($variable);
        }

        return gettype($variable);
    }
}
