<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Di\Helpers\DefinitionNormalizer;
use Yiisoft\Di\Helpers\DefinitionParser;
use Yiisoft\Di\Helpers\TagHelper;

use function array_key_exists;
use function array_keys;
use function get_class;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_callable;
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
     * @var DefinitionStorage Storage of object definitions.
     */
    private DefinitionStorage $definitions;

    /**
     * @var array Used to collect IDs of objects instantiated during build
     * to detect circular references.
     */
    private array $building = [];

    /**
     * @var bool $validate If definitions should be validated.
     */
    private bool $validate;

    private array $instances = [];

    private CompositeContainer $delegates;

    /**
     * @var array Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     * @psalm-var array<string, list<string>>
     */
    private array $tags;

    /**
     * @var Closure[]
     */
    private array $resetters = [];
    private bool $useResettersFromMeta = true;

    /**
     * @param ContainerConfigInterface $config Container configuration.
     *
     * @throws InvalidConfigException If configuration is not valid.
     */
    public function __construct(ContainerConfigInterface $config)
    {
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
        if (TagHelper::isTagAlias($id)) {
            $tag = TagHelper::extractTagFromAlias($id);
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
    public function get(string $id)
    {
        if (!array_key_exists($id, $this->instances)) {
            try {
                $this->instances[$id] = $this->build($id);
            } catch (NotFoundException $e) {
                if (!$this->delegates->has($id)) {
                    throw $e;
                }

                /** @psalm-suppress MixedReturnStatement */
                return $this->delegates->get($id);
            }
        }

        if ($id === StateResetter::class) {
            $delegatesResetter = null;
            if ($this->delegates->has(StateResetter::class)) {
                $delegatesResetter = $this->delegates->get(StateResetter::class);
            }

            /** @var StateResetter $mainResetter */
            $mainResetter = $this->instances[$id];

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
        }

        /** @psalm-suppress MixedReturnStatement */
        return $this->instances[$id];
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
    private function addDefinition(string $id, $definition): void
    {
        /** @var mixed $definition */
        [$definition, $meta] = DefinitionParser::parse($definition);
        if ($this->validate) {
            $this->validateDefinition($definition, $id);
            $this->validateMeta($meta);
        }
        /**
         * @psalm-var array{reset?:Closure,tags?:string[]} $meta
         */

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
        /** @var mixed $definition */
        foreach ($config as $id => $definition) {
            if ($this->validate && !is_string($id)) {
                throw new InvalidConfigException(
                    sprintf(
                        'Key must be a string. %s given.',
                        $this->getVariableType($id)
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
     * Each delegate must is a callable in format "function (ContainerInterface $container): ContainerInterface".
     * The container instance returned is used in case a service can not be found in primary container.
     *
     * @param array $delegates
     *
     * @throws InvalidConfigException
     */
    private function setDelegates(array $delegates): void
    {
        $this->delegates = new CompositeContainer();
        foreach ($delegates as $delegate) {
            if (!$delegate instanceof Closure) {
                throw new InvalidConfigException(
                    'Delegate must be callable in format "function (ContainerInterface $container): ContainerInterface".'
                );
            }

            /** @var ContainerInterface */
            $delegate = $delegate($this);

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
    private function validateDefinition($definition, ?string $id = null): void
    {
        if (is_array($definition) && isset($definition[DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA])) {
            /** @var mixed $class */
            $class = $definition['class'];

            /** @var mixed $constructorArguments */
            $constructorArguments = $definition['__construct()'];

            /**
             * @var array $methodsAndProperties Is always array for prepared array definition data.
             *
             * @see DefinitionParser::parse()
             */
            $methodsAndProperties = $definition['methodsAndProperties'];

            $definition = array_merge(
                $class === null ? [] : [ArrayDefinition::CLASS_NAME => $class],
                [ArrayDefinition::CONSTRUCTOR => $constructorArguments],
                $methodsAndProperties,
            );
        }

        if ($definition instanceof ExtensibleService) {
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
        /** @var mixed $value */
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
     * @param mixed $tags
     *
     * @throws InvalidConfigException
     */
    private function validateDefinitionTags($tags): void
    {
        if (!is_array($tags)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: tags should be array of strings, %s given.',
                    $this->getVariableType($tags)
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
     * @param mixed $reset
     *
     * @throws InvalidConfigException
     */
    private function validateDefinitionReset($reset): void
    {
        if (!$reset instanceof Closure) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: "reset" should be closure, %s given.',
                    $this->getVariableType($reset)
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
                            $this->getVariableType($services)
                        )
                    );
                }
                /** @var mixed $service */
                foreach ($services as $service) {
                    if (!is_string($service)) {
                        throw new InvalidConfigException(
                            sprintf(
                                'Invalid tags configuration: service should be defined as class string, %s given.',
                                $this->getVariableType($service)
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
     * @see $definitions
     *
     * @param string $id ID to set definition for.
     * @param mixed|object $definition Definition to set.
     */
    private function addDefinitionToStorage(string $id, $definition): void
    {
        $this->definitions->set($id, $definition);

        if ($id === StateResetter::class) {
            $this->useResettersFromMeta = false;
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
        if (TagHelper::isTagAlias($id)) {
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
            /** @var mixed $object */
            $object = $this->buildInternal($id);
        } finally {
            unset($this->building[$id]);
        }

        return $object;
    }

    private function getTaggedServices(string $tagAlias): array
    {
        $tag = TagHelper::extractTagFromAlias($tagAlias);
        $services = [];
        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $service) {
                /** @var mixed */
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

            return $definition->resolve($this->get(ContainerInterface::class));
        }

        throw new NotFoundException($id, $this->definitions->getBuildStack());
    }

    /**
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     */
    private function addProviders(array $providers): void
    {
        $extensions = [];
        /** @var mixed $provider */
        foreach ($providers as $provider) {
            $providerInstance = $this->buildProvider($provider);
            $extensions[] = $providerInstance->getExtensions();
            $this->addDefinitions($providerInstance->getDefinitions());
        }

        foreach ($extensions as $providerExtensions) {
            /** @var mixed $extension */
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
                            $this->getVariableType($extension)
                        )
                    );
                }

                /** @var mixed $definition */
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
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    private function buildProvider($provider): ServiceProviderInterface
    {
        if ($this->validate && !(is_string($provider) || $provider instanceof ServiceProviderInterface)) {
            throw new InvalidConfigException(
                sprintf(
                    'Service provider should be a class name or an instance of %s. %s given.',
                    ServiceProviderInterface::class,
                    $this->getVariableType($provider)
                )
            );
        }

        /** @psalm-var class-string|ServiceProviderInterface $provider */

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
