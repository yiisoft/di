<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Di\Helpers\DefinitionNormalizer;
use Yiisoft\Di\Helpers\DefinitionParser;
use Yiisoft\Di\Reference\TagReference;

use function array_key_exists;
use function array_keys;
use function implode;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Container implements a [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container.
 */
final class Container implements ContainerInterface
{
    private const META_TAGS = 'tags';
    private const META_RESET = 'reset';
    private const ALLOWED_META = [self::META_TAGS, self::META_RESET];

    /**
     * @var DefinitionStorage Storage of object definitions.
     */
    private readonly DefinitionStorage $definitions;

    /**
     * @var array Used to collect IDs of objects instantiated during build
     * to detect circular references.
     */
    private array $building = [];

    /**
     * @var bool $validate If definitions should be validated.
     */
    private readonly bool $validate;

    /**
     * @var array Cached instances.
     * @psalm-var array<string, mixed>
     */
    private array $instances = [];

    private CompositeContainer $delegates;

    /**
     * @var array Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     * @psalm-var array<string, list<string>>
     */
    private array $tags;

    /**
     * @var Closure[]
     * @psalm-var array<string, Closure>
     */
    private array $resetters = [];
    private bool $useResettersFromMeta = true;

    /**
     * @param ?ContainerConfigInterface $config Container configuration.
     *
     * @throws InvalidConfigException If configuration is not valid.
     */
    public function __construct(?ContainerConfigInterface $config = null)
    {
        $config ??= ContainerConfig::create();

        $this->definitions = new DefinitionStorage(
            [
                ContainerInterface::class => $this,
                StateResetter::class => StateResetter::class,
            ],
            $config->useStrictMode()
        );
        $this->validate = $config->shouldValidate();
        $this->setTags($config->getTags());
        $this->addDefinitions($config->getDefinitions());
        $this->addProviders($config->getProviders());
        $this->setDelegates($config->getDelegates());
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     *
     * @param string $id Class name, interface name or alias name.
     *
     * @return bool Whether the container is able to provide instance of class specified.
     *
     * @see addDefinition()
     */
    public function has(string $id): bool
    {
        try {
            if ($this->definitions->has($id)) {
                return true;
            }
        } catch (CircularReferenceException) {
            return true;
        }

        if (TagReference::isTagAlias($id)) {
            $tag = TagReference::extractTagFromAlias($id);
            return isset($this->tags[$tag]);
        }

        return false;
    }

    /**
     * Returns an instance by either interface name or alias.
     *
     * The same instance of the class will be returned each time this method is called.
     *
     * @param string $id The interface or an alias name that was previously registered.
     *
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundExceptionInterface
     * @throws NotInstantiableException
     * @throws BuildingException
     *
     * @return mixed An instance of the requested interface.
     *
     * @psalm-template T
     * @psalm-param string|class-string<T> $id
     * @psalm-return ($id is class-string ? T : mixed)
     *
     * @psalm-suppress MixedReturnStatement `mixed` is a correct return type for this method.
     */
    public function get(string $id)
    {
        // Fast path: check if instance exists.
        if (array_key_exists($id, $this->instances)) {
            if ($id === StateResetter::class) {
                return $this->prepareStateResetter();
            }
            return $this->instances[$id];
        }

        try {
            $this->instances[$id] = $this->build($id);
        } catch (NotFoundException $exception) {
            // Fast path: if the exception ID matches the requested ID, no need to modify stack.
            if ($exception->getId() === $id) {
                // Try delegates before giving up.
                try {
                    if ($this->delegates->has($id)) {
                        return $this->delegates->get($id);
                    }
                } catch (Throwable $e) {
                    throw new BuildingException($id, $e, $this->definitions->getBuildStack(), $e);
                }
                throw $exception;
            }

            // Add current ID to build stack for better error reporting.
            $buildStack = $exception->getBuildStack();
            array_unshift($buildStack, $id);
            throw new NotFoundException($exception->getId(), $buildStack);
        } catch (NotFoundExceptionInterface $exception) {
            // Try delegates before giving up
            try {
                if ($this->delegates->has($id)) {
                    return $this->delegates->get($id);
                }
            } catch (Throwable $e) {
                throw new BuildingException($id, $e, $this->definitions->getBuildStack(), $e);
            }

            throw new NotFoundException($id, [$id], previous: $exception);
        } catch (ContainerExceptionInterface $e) {
            if (!$e instanceof InvalidConfigException) {
                throw $e;
            }
            throw new BuildingException($id, $e, $this->definitions->getBuildStack(), $e);
        } catch (Throwable $e) {
            throw new BuildingException($id, $e, $this->definitions->getBuildStack(), $e);
        }

        // Handle StateResetter for newly built instances.
        if ($id === StateResetter::class) {
            return $this->prepareStateResetter();
        }

        return $this->instances[$id];
    }

    private function prepareStateResetter(): StateResetter
    {
        $delegatesResetter = null;
        if ($this->delegates->has(StateResetter::class)) {
            $delegatesResetter = $this->delegates->get(StateResetter::class);
        }

        /** @var StateResetter $mainResetter */
        $mainResetter = $this->instances[StateResetter::class];

        if ($this->useResettersFromMeta) {
            /** @var StateResetter[] $resetters */
            $resetters = [];
            foreach ($this->resetters as $serviceId => $callback) {
                if (isset($this->instances[$serviceId])) {
                    $resetters[$serviceId] = $callback;
                }
            }
            if ($delegatesResetter !== null) {
                $resetters[] = $delegatesResetter;
            }
            $mainResetter->setResetters($resetters);
        } elseif ($delegatesResetter !== null) {
            $resetter = new StateResetter($this->get(ContainerInterface::class));
            $resetter->setResetters([$mainResetter, $delegatesResetter]);

            return $resetter;
        }

        return $mainResetter;
    }

    /**
     * Sets a definition to the container. Definition may be defined multiple ways.
     *
     * @param string $id ID to set definition for.
     * @param mixed $definition Definition to set.
     *
     * @throws InvalidConfigException
     *
     * @see DefinitionNormalizer::normalize()
     */
    private function addDefinition(string $id, mixed $definition): void
    {
        [$definition, $meta] = DefinitionParser::parse($definition);
        if ($this->validate) {
            $this->validateDefinition($definition, $id);
            // Only validate meta if it's not empty.
            if ($meta !== []) {
                $this->validateMeta($meta);
            }
        }
        /**
         * @psalm-var array{reset?:Closure,tags?:string[]} $meta
         */

        // Process meta only if it has tags or reset callback.
        if (isset($meta[self::META_TAGS])) {
            $this->setDefinitionTags($id, $meta[self::META_TAGS]);
        }
        if (isset($meta[self::META_RESET])) {
            $this->setDefinitionResetter($id, $meta[self::META_RESET]);
        }

        unset($this->instances[$id]);

        $this->addDefinitionToStorage($id, $definition);
    }

    /**
     * Sets multiple definitions at once.
     *
     * @param array $config Definitions indexed by their IDs.
     *
     * @throws InvalidConfigException
     */
    private function addDefinitions(array $config): void
    {
        foreach ($config as $id => $definition) {
            if ($this->validate && !is_string($id)) {
                throw new InvalidConfigException(
                    sprintf(
                        'Key must be a string. %s given.',
                        get_debug_type($id)
                    )
                );
            }
            /** @var string $id */

            $this->addDefinition($id, $definition);
        }
    }

    /**
     * Set container delegates.
     *
     * Each delegate must be a callable in format `function (ContainerInterface $container): ContainerInterface`.
     * The container instance returned is used in case a service can't be found in primary container.
     *
     * @throws InvalidConfigException
     */
    private function setDelegates(array $delegates): void
    {
        $this->delegates = new CompositeContainer();

        $container = $this->get(ContainerInterface::class);

        foreach ($delegates as $delegate) {
            if (!$delegate instanceof Closure) {
                throw new InvalidConfigException(
                    'Delegate must be callable in format "function (ContainerInterface $container): ContainerInterface".'
                );
            }

            $delegate = $delegate($container);

            if (!$delegate instanceof ContainerInterface) {
                throw new InvalidConfigException(
                    'Delegate callable must return an object that implements ContainerInterface.'
                );
            }

            $this->delegates->attach($delegate);
        }
        $this->definitions->setDelegateContainer($this->delegates);
    }

    /**
     * @param mixed $definition Definition to validate.
     * @param string|null $id ID of the definition to validate.
     *
     * @throws InvalidConfigException
     */
    private function validateDefinition(mixed $definition, ?string $id = null): void
    {
        // Skip validation for common simple cases.
        if ($definition instanceof ContainerInterface || $definition instanceof Closure) {
            return;
        }

        if (is_array($definition)) {
            if (isset($definition[DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA])) {
                $class = $definition['class'];
                $constructorArguments = $definition['__construct()'];

                /**
                 * @var array $methodsAndProperties Is always array for prepared array definition data.
                 * @see DefinitionParser::parse()
                 * @psalm-var array<string,mixed> $methodsAndProperties
                 */
                $methodsAndProperties = $definition['methodsAndProperties'];

                $definition = array_merge(
                    $class === null ? [] : [ArrayDefinition::CLASS_NAME => $class],
                    [ArrayDefinition::CONSTRUCTOR => $constructorArguments],
                    // extract only value from parsed definition method
                    array_map(static fn (array $data): mixed => $data[2], $methodsAndProperties),
                );
            }
        } elseif ($definition instanceof ExtensibleService) {
            throw new InvalidConfigException(
                'Invalid definition. ExtensibleService is only allowed in provider extensions.'
            );
        }

        DefinitionValidator::validate($definition, $id);
    }

    /**
     * @throws InvalidConfigException
     */
    private function validateMeta(array $meta): void
    {
        foreach ($meta as $key => $value) {
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

            if ($key === self::META_TAGS) {
                $this->validateDefinitionTags($value);
            }

            if ($key === self::META_RESET) {
                $this->validateDefinitionReset($value);
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private function validateDefinitionTags(mixed $tags): void
    {
        if (!is_array($tags)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: tags should be array of strings, %s given.',
                    get_debug_type($tags)
                )
            );
        }

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidConfigException('Invalid tag. Expected a string, got ' . var_export($tag, true) . '.');
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private function validateDefinitionReset(mixed $reset): void
    {
        if (!$reset instanceof Closure) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: "reset" should be closure, %s given.',
                    get_debug_type($reset)
                )
            );
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private function setTags(array $tags): void
    {
        if ($this->validate) {
            foreach ($tags as $tag => $services) {
                if (!is_string($tag)) {
                    throw new InvalidConfigException(
                        sprintf(
                            'Invalid tags configuration: tag should be string, %s given.',
                            $tag
                        )
                    );
                }
                if (!is_array($services)) {
                    throw new InvalidConfigException(
                        sprintf(
                            'Invalid tags configuration: tag should contain array of service IDs, %s given.',
                            get_debug_type($services)
                        )
                    );
                }
                foreach ($services as $service) {
                    if (!is_string($service)) {
                        throw new InvalidConfigException(
                            sprintf(
                                'Invalid tags configuration: service should be defined as class string, %s given.',
                                get_debug_type($service)
                            )
                        );
                    }
                }
            }
        }
        /** @psalm-var array<string, list<string>> $tags */

        $this->tags = $tags;
    }

    /**
     * @psalm-param string[] $tags
     */
    private function setDefinitionTags(string $id, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag]) || !in_array($id, $this->tags[$tag], true)) {
                $this->tags[$tag][] = $id;
            }
        }
    }

    private function setDefinitionResetter(string $id, Closure $resetter): void
    {
        $this->resetters[$id] = $resetter;
    }

    /**
     * Add definition to storage.
     *
     * @param string $id ID to set definition for.
     * @param mixed|object $definition Definition to set.
     *
     * @see $definitions
     */
    private function addDefinitionToStorage(string $id, mixed $definition): void
    {
        $this->definitions->set($id, $definition);

        if ($id === StateResetter::class) {
            $this->useResettersFromMeta = false;
        }
    }

    /**
     * Creates new instance by either interface name or alias.
     *
     * @param string $id The interface or the alias name that was previously registered.
     *
     * @throws InvalidConfigException
     * @throws NotFoundExceptionInterface
     * @throws CircularReferenceException
     *
     * @return mixed|object New-built instance of the specified class.
     *
     * @internal
     */
    private function build(string $id): mixed
    {
        // Fast path: check for circular reference first as it's the most critical.
        if (isset($this->building[$id])) {
            if ($id === ContainerInterface::class) {
                return $this;
            }
            throw new CircularReferenceException(
                sprintf(
                    'Circular reference to "%s" detected while building: %s.',
                    $id,
                    implode(', ', array_keys($this->building))
                )
            );
        }

        // Less common case: tag alias.
        if (TagReference::isTagAlias($id)) {
            return $this->getTaggedServices($id);
        }

        // Check if the definition exists.
        if (!$this->definitions->has($id)) {
            throw new NotFoundException($id, $this->definitions->getBuildStack());
        }

        $this->building[$id] = 1;
        try {
            $normalizedDefinition = DefinitionNormalizer::normalize($this->definitions->get($id), $id);
            $object = $normalizedDefinition->resolve($this->get(ContainerInterface::class));
        } finally {
            unset($this->building[$id]);
        }

        return $object;
    }

    private function getTaggedServices(string $tagAlias): array
    {
        $tag = TagReference::extractTagFromAlias($tagAlias);
        $services = [];
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $service) {
                $services[] = $this->get($service);
            }
        }

        return $services;
    }

    /**
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     */
    private function addProviders(array $providers): void
    {
        $extensions = [];
        foreach ($providers as $provider) {
            $providerInstance = $this->buildProvider($provider);
            $extensions[] = $providerInstance->getExtensions();
            $this->addDefinitions($providerInstance->getDefinitions());
        }

        foreach ($extensions as $providerExtensions) {
            foreach ($providerExtensions as $id => $extension) {
                if (!is_string($id)) {
                    throw new InvalidConfigException(
                        sprintf('Extension key must be a service ID as string, %s given.', $id)
                    );
                }

                if ($id === ContainerInterface::class) {
                    throw new InvalidConfigException('ContainerInterface extensions are not allowed.');
                }

                if (!$this->definitions->has($id)) {
                    throw new InvalidConfigException("Extended service \"$id\" doesn't exist.");
                }

                if (!is_callable($extension)) {
                    throw new InvalidConfigException(
                        sprintf(
                            'Extension of service should be callable, %s given.',
                            get_debug_type($extension)
                        )
                    );
                }

                $definition = $this->definitions->get($id);
                if (!$definition instanceof ExtensibleService) {
                    $definition = new ExtensibleService($definition, $id);
                    $this->addDefinitionToStorage($id, $definition);
                }

                $definition->addExtension($extension);
            }
        }
    }

    /**
     * Builds service provider by definition.
     *
     * @param mixed $provider Class name or instance of provider.
     *
     * @throws InvalidConfigException If provider argument is not valid.
     *
     * @return ServiceProviderInterface Instance of service provider.
     */
    private function buildProvider(mixed $provider): ServiceProviderInterface
    {
        if ($this->validate && !(is_string($provider) || $provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                sprintf(
                    'Service provider should be a class name or an instance of %s. %s given.',
                    ServiceProviderInterface::class,
                    get_debug_type($provider)
                )
            );
        }

        /**
         * @psalm-suppress MixedMethodCall Service provider defined as class string
         * should container public constructor, otherwise throws error.
         */
        $providerInstance = is_object($provider) ? $provider : new $provider();
        if (!$providerInstance instanceof ServiceProviderInterface) {
            throw new InvalidConfigException(
                sprintf(
                    'Service provider should be an instance of %s. %s given.',
                    ServiceProviderInterface::class,
                    get_debug_type($providerInstance)
                )
            );
        }

        return $providerInstance;
    }
}
