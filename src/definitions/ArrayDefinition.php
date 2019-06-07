<?php
namespace yii\di\definitions;

use Psr\Container\ContainerInterface;
use yii\di\contracts\Definition;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotInstantiableException;
use yii\di\resolvers\ClassNameResolver;

/**
 * Builds object by array config.
 * @package yii\di
 */
class ArrayDefinition implements Definition
{
    private $config;

    private static $dependencies = [];

    private const CLASS_KEY = '__class';
    private const CONSTRUCT_KEY = '__construct()';

    /**
     * Injector constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getArray(): array
    {
        return $this->config;
    }

    /**
     * @param string $class
     * @return self
     */
    public static function fromClassName(string $class): self
    {
        return new static([self::CLASS_KEY => $class]);
    }

    public function resolve(ContainerInterface $container, array $params = [])
    {
        $config = $this->config;

        if (empty($config[self::CLASS_KEY])) {
            throw new NotInstantiableException(var_export($config, true));
        }

        if (!empty($params)) {
            $config[self::CONSTRUCT_KEY] = array_merge($config[self::CONSTRUCT_KEY] ?? [], $params);
        }

        return $this->buildFromArray($container, $config);
    }

    private function buildFromArray(ContainerInterface $container, array $config)
    {
        if (empty($config[self::CLASS_KEY])) {
            throw new NotInstantiableException(var_export($config, true));
        }
        $class = $config[self::CLASS_KEY];
        unset($config[self::CLASS_KEY]);

        $dependencies = $this->getDependencies($class);

        if (isset($config[self::CONSTRUCT_KEY])) {
            foreach (array_values($config[self::CONSTRUCT_KEY]) as $index => $param) {
                if ($param instanceof Definition) {
                    $dependencies[$index] = $param;
                } else {
                    $dependencies[$index] = new ValueDefinition($param);
                }
            }
            unset($config[self::CONSTRUCT_KEY]);
        }

        $resolved = $this->resolveDependencies($container, $dependencies);
        $object = new $class(...$resolved);
        return $this->configure($container, $object, $config);
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     * @param Definition[] $dependencies the dependencies
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    private function resolveDependencies(ContainerInterface $container, array $dependencies): array
    {
        $container = $container->parentContainer ?? $container;
        $result = [];
        /** @var Definition $dependency */
        foreach ($dependencies as $dependency) {
            $result[] = $this->resolveDependency($container, $dependency);
        }

        return $result;
    }

    /**
     * This function resolves a dependency recursively, checking for loops.
     * TODO add checking for loops
     * @param Definition $dependency
     * @return mixed
     */
    private function resolveDependency(ContainerInterface $container, Definition $dependency)
    {
        while ($dependency instanceof Definition) {
            $dependency = $dependency->resolve($container);
        }
        return $dependency;
    }

    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return Definition[] the dependencies of the specified class.
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @internal
     */
    private function getDependencies(string $class): array
    {
        if (!isset($this->dependencies[$class])) {
            // For now use hard coded resolver.
            $resolver = new ClassNameResolver();

            self::$dependencies[$class] = $resolver->resolveConstructor($class);
        }

        return self::$dependencies[$class];
    }

    /**
     * Configures an object with the given configuration.
     * @param ContainerInterface $container
     * @param object $object the object to be configured
     * @param iterable $config property values and methods to call
     * @return object the object itself
     */
    private function configure(ContainerInterface $container, $object, iterable $config)
    {
        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                \call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                if ($arguments instanceof Definition) {
                    $arguments = $arguments->resolve($container);
                }
                $object->$action = $arguments;
            }
        }

        return $object;
    }
}
