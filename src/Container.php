<?php

namespace Yiisoft\Di;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\DeferredServiceProviderInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Factory\Exceptions\CircularReferenceException;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Factory\Exceptions\NotFoundException;
use Yiisoft\Factory\Exceptions\NotInstantiableException;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Definitions\ArrayDefinition;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
class Container implements ContainerInterface
{
    /**
     * @var DefinitionInterface[]|DeferredServiceProviderInterface[]|ReferenceStorage object definitions indexed by their types
     */
    private ReferenceStorage $definitions;
    /**
     * @var ReferenceStorage used to collect ids instantiated during build
     * to detect circular references
     */
    private ReferenceStorage $building;

    /**
     * @var ReferenceStorage
     */
    private ReferenceStorage $instances;

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
        $this->definitions = new ReferenceStorage();
        $this->instances = new ReferenceStorage();
        $this->building = new ReferenceStorage();
        $this->setMultiple($definitions);

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
     * @param array $parameters parameters to set for the object obtained
     * @return object an instance of the requested interface.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    public function get($id, array $parameters = [])
    {
        $ref = $this->getReference($id);
        if (!$this->instances->contains($ref)) {
            $this->instances->attach($ref, $this->build($ref, $parameters));
        }

        return $this->instances[$ref];
    }

    /**
     * @param Reference|string $id
     * @return string
     */
    public function getReference($id): Reference
    {
        return is_string($id) ? Reference::to($id) : $id;
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param Reference $ref reference for the interface or an alias name that was previously registered via [[set()]].
     * @param array $params
     * @return object new built instance of the specified class.
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @internal
     */
    protected function build(Reference $ref, array $params = [])
    {
        if ($this->building->contains($ref)) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s',
                $ref->getId(),
                $this->implodeBuildingKeys($this->building)
            ));
        }

        $this->building->attach($ref);
        $object = $this->buildInternal($ref, $params);
        $this->building->detach($ref);

        return $object;
    }

    private function implodeBuildingKeys(ReferenceStorage $storage): string
    {
        $result = '';
        foreach ($storage as $ref) {
            $result .= $ref->getId() . ',';
        }

        return rtrim($result, ',');
    }

    /**
     * @param Reference $ref
     * @param array $params
     *
     * @return mixed|object
     * @throws InvalidConfigException
     * @throws NotFoundException
     */
    private function buildInternal(Reference $ref, array $params = [])
    {
        if (!$this->definitions->contains($ref)) {
            return $this->buildPrimitive($ref->getId(), $params);
        }

        $this->processDefinition($this->definitions[$ref]);

        return $this->definitions[$ref]->resolve($this, $params);
    }

    protected function processDefinition($definition): void
    {
        if ($definition instanceof DeferredServiceProviderInterface) {
            $definition->register($this);
        }
    }

    /**
     * @param string $class
     * @param array $params
     *
     * @return mixed|object
     * @throws InvalidConfigException
     * @throws NotFoundException
     */
    private function buildPrimitive(string $class, array $params = [])
    {
        if (class_exists($class)) {
            $definition = new ArrayDefinition($class);

            return $definition->resolve($this, $params);
        }

        throw new NotFoundException("No definition for $class");
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
        $ref = $this->getReference($id);
        $this->instances->detach($ref);
        $this->definitions->attach($ref, Normalizer::normalize($definition, $id));
    }

    /**
     * Sets multiple definitions at once.
     * @param array $config definitions indexed by their ids
     * @throws InvalidConfigException
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
     * @see set()
     */
    public function has($id): bool
    {
        return $this->definitions->contains($this->getReference($id)) || class_exists($id);
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
    public function addProvider($providerDefinition): void
    {
        $provider = $this->buildProvider($providerDefinition);

        if ($provider instanceof DeferredServiceProviderInterface) {
            foreach ($provider->provides() as $id) {
                $this->definitions->attach($this->getReference($id), $provider);
            }
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

    /**
     * Returns a value indicating whether the container has already instantiated
     * instance of the specified name.
     * @param string|Reference $id class name, interface name or alias name
     * @return bool whether the container has instance of class specified.
     */
    public function hasInstance($id): bool
    {
        return $this->instances->contains($this->getReference($id));
    }

    /**
     * Returns all instances set in container
     * @return ReferenceStorage list of instance
     */
    public function getInstances(): ReferenceStorage
    {
        return $this->instances;
    }
}
