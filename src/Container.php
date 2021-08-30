<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Infrastructure\DefinitionValidator;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;

use function array_key_exists;
use function array_keys;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 */
final class Container implements ContainerInterface
{
    private const META_TAGS = 'tags';
    private const META_RESET = 'reset';
    private const ALLOWED_META = [self::META_TAGS, self::META_RESET];

    /**
     * @var DefinitionStorage storage of object definitions
     */
    private DefinitionStorage $definitions;
    /**
     * @var array used to collect ids instantiated during build
     * to detect circular references
     */
    private array $building = [];

    /**
     * @var bool $validate Validate definitions when set
     */
    private bool $validate;

    /**
     * @var object[]
     */
    private array $instances = [];

    private CompositeContainer $delegates;

    private array $tags;

    private array $resetters = [];
    /** @psalm-suppress PropertyNotSetInConstructor */
    private DependencyResolver $dependencyResolver;

    /**
     * Container constructor.
     *
     * @param array $definitions Definitions to put into container.
     * @param array $providers Service providers to get definitions from.
     * lookup to when resolving dependencies. If provided the current container
     * is no longer queried for dependencies.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public function __construct(
        array $definitions = [],
        array $providers = [],
        array $tags = [],
        bool $validate = true,
        array $delegates = []
    ) {
        $this->tags = $tags;
        $this->validate = $validate;
        $this->definitions = new DefinitionStorage();
        $this->setDefaultDefinitions();
        $this->setMultiple($definitions);
        $this->addProviders($providers);
        $this->dependencyResolver = new DependencyResolver($this);
        $this->dependencyResolver = new DependencyResolver($this->get(ContainerInterface::class));
        $this->setDelegates($delegates);
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
        /** @psalm-suppress  DocblockTypeContradiction */
        if (!is_string($id)) {
            return false;
        }

        if ($this->isTagAlias($id)) {
            $tag = substr($id, 4);
            return isset($this->tags[$tag]);
        }

        try {
            return $this->definitions->has($id);
        } catch (CircularReferenceException $e) {
            return true;
        }
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
        /** @psalm-suppress TypeDoesNotContainType */
        if (!is_string($id)) {
            throw new \InvalidArgumentException("Id must be a string, {$this->getVariableType($id)} given.");
        }

        if (!array_key_exists($id, $this->instances)) {
            try {
                $this->instances[$id] = $this->build($id);
            } catch (NotFoundException $e) {
                if (!$this->delegates->has($id)) {
                    throw $e;
                }

                return $this->delegates->get($id);
            }
        }

        if ($id === StateResetter::class && $this->definitions->get($id) === StateResetter::class) {
            $resetters = [];
            foreach ($this->resetters as $serviceId => $callback) {
                if (isset($this->instances[$serviceId])) {
                    $resetters[$serviceId] = $callback;
                }
            }
            if ($this->delegates->has(StateResetter::class)) {
                $resetters[] = $this->delegates->get(StateResetter::class);
            }
            $this->instances[$id]->setResetters($resetters);
        }

        return $this->instances[$id];
    }

    /**
     * Sets a definition to the container. Definition may be defined multiple ways.
     *
     * @param string $id
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     *
     * @see `DefinitionNormalizer::normalize()`
     */
    private function set(string $id, $definition): void
    {
        [$definition, $meta] = DefinitionParser::parse($definition);
        if ($this->validate) {
            $this->validateDefinition($definition, $id);
            $this->validateMeta($meta);
        }

        if (isset($meta[self::META_TAGS])) {
            if ($this->validate) {
                $this->validateTags($meta[self::META_TAGS]);
            }
            $this->setTags($id, $meta[self::META_TAGS]);
        }
        if (isset($meta[self::META_RESET])) {
            $this->setResetter($id, $meta[self::META_RESET]);
        }

        unset($this->instances[$id]);
        $this->definitions->set($id, $definition);
    }

    /**
     * Sets multiple definitions at once.
     *
     * @param array $config definitions indexed by their ids
     *
     * @throws InvalidConfigException
     */
    private function setMultiple(array $config): void
    {
        foreach ($config as $id => $definition) {
            if ($this->validate && !is_string($id)) {
                throw new InvalidConfigException(sprintf('Key must be a string. %s given.', $this->getVariableType($id)));
            }
            $this->set($id, $definition);
        }
    }

    private function setDefaultDefinitions(): void
    {
        $this->setMultiple([
            ContainerInterface::class => $this,
            StateResetter::class => StateResetter::class,
        ]);
    }

    /**
     * @param array $delegates
     *
     * @throws InvalidConfigException
     */
    private function setDelegates(array $delegates): void
    {
        $this->delegates = new CompositeContainer();
        foreach ($delegates as $delegate) {
            if (!$delegate instanceof \Closure) {
                throw new InvalidConfigException(
                    'Delegate must be callable in format "fn (ContainerInterface $conatiner) => MyContainer($container)"'
                );
            }

            $delegate = $delegate($this);

            if (!$delegate instanceof ContainerInterface) {
                throw new InvalidConfigException(
                    'Delegate callable must return an object that implements ContainerInterface'
                );
            }

            $this->delegates->attach($delegate);
        }
        $this->definitions->setDelegateContainer($this->delegates);
    }

    /**
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     */
    private function validateDefinition($definition, ?string $id = null): void
    {
        if (is_array($definition) && isset($definition[DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA])) {
            $class = $definition['class'];
            $constructorArguments = $definition['__construct()'];
            $methodsAndProperties = $definition['methodsAndProperties'];
            $definition = array_merge(
                $class === null ? [] : [ArrayDefinition::CLASS_NAME => $class],
                [ArrayDefinition::CONSTRUCTOR => $constructorArguments],
                $methodsAndProperties,
            );
        }

        if ($definition instanceof ExtensibleService) {
            throw new InvalidConfigException('Invalid definition. ExtensibleService is only allowed in provider extensions.');
        }

        DefinitionValidator::validate($definition, $id);
    }

    /**
     * @throws InvalidConfigException
     */
    private function validateMeta(array $meta): void
    {
        foreach ($meta as $key => $_value) {
            if (!in_array($key, self::ALLOWED_META, true)) {
                throw new InvalidConfigException(
                    sprintf(
                        'Invalid definition: metadata "%s" is not allowed. Did you mean "%s()" or "$%s"?',
                        $key,
                        $key,
                        $key,
                    )
                );
            }
        }
    }

    private function validateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidConfigException('Invalid tag. Expected a string, got ' . var_export($tag, true) . '.');
            }
        }
    }

    private function setTags(string $id, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag]) || !in_array($id, $this->tags[$tag], true)) {
                $this->tags[$tag][] = $id;
            }
        }
    }

    private function setResetter(string $id, Closure $resetter): void
    {
        $this->resetters[$id] = $resetter;
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
        if ($this->isTagAlias($id)) {
            return $this->getTaggedServices($id);
        }

        if (isset($this->building[$id])) {
            if ($id === ContainerInterface::class) {
                return $this;
            }
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(', ', array_keys($this->building))
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

    private function isTagAlias(string $id): bool
    {
        return strpos($id, 'tag@') === 0;
    }

    private function getTaggedServices(string $tagAlias): array
    {
        $tag = substr($tagAlias, 4);
        $services = [];
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $service) {
                $services[] = $this->get($service);
            }
        }

        return $services;
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
        if ($this->definitions->has($id)) {
            $definition = DefinitionNormalizer::normalize($this->definitions->get($id), $id);

            return $definition->resolve($this->dependencyResolver);
        }

        throw new NotFoundException($id, $this->definitions->getLastBuilding());
    }

    private function addProviders(array $providers): void
    {
        $extensions = [];
        foreach ($providers as $provider) {
            $providerInstance = $this->buildProvider($provider);
            $extensions[] = $providerInstance->getExtensions();
            $this->addProviderDefinitions($providerInstance);
        }

        foreach ($extensions as $providerExtensions) {
            foreach ($providerExtensions as $id => $extension) {
                if (!$this->definitions->has($id)) {
                    throw new InvalidConfigException("Extended service \"$id\" doesn't exist.");
                }

                $definition = $this->definitions->get($id);
                if (!$definition instanceof ExtensibleService) {
                    $definition = new ExtensibleService($definition);
                    $this->definitions->set($id, $definition);
                }

                $definition->addExtension($extension);


            }
        }
    }

    /**
     * Adds service provider definitions to the container.
     *
     * @param object $provider
     *
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     *
     * @see ServiceProviderInterface
     */
    private function addProviderDefinitions($provider): void
    {
        $definitions = $provider->getDefinitions();
        $this->setMultiple($definitions);
    }

    /**
     * Builds service provider by definition.
     *
     * @param mixed $provider Class name or instance of provider.
     *
     * @throws InvalidConfigException If provider argument is not valid.
     *
     * @return ServiceProviderInterface Instance of service provider.
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    private function buildProvider($provider): ServiceProviderInterface
    {
        if ($this->validate && !(is_string($provider) || is_object($provider) && $provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                sprintf(
                    'Service provider should be a class name or an instance of %s. %s given.',
                    ServiceProviderInterface::class,
                    $this->getVariableType($provider)
                )
            );
        }

        $providerInstance = is_object($provider) ? $provider : new $provider();
        if (!$providerInstance instanceof ServiceProviderInterface) {
            throw new InvalidConfigException(
                sprintf(
                    'Service provider should be an instance of %s. %s given.',
                    ServiceProviderInterface::class,
                    $this->getVariableType($providerInstance)
                )
            );
        }

        /**
         * @psalm-suppress LessSpecificReturnStatement
         */
        return $providerInstance;
    }

    /**
     * @param mixed $variable
     */
    private function getVariableType($variable): string
    {
        return is_object($variable) ? get_class($variable) : gettype($variable);
    }
}
