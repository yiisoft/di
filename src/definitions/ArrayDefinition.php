<?php

namespace yii\di\definitions;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DefinitionInterface;
use yii\di\exceptions\NotInstantiableException;
use yii\di\resolvers\ClassNameResolver;
use yii\di\Reference;

/**
 * Class Resolver builds object by array config.
 * @package yii\di
 */
class ArrayDefinition implements DefinitionInterface
{
    private $config;

    private static $dependencies = [];

    /**
     * Injector constructor.
     * @param $container
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getArray(): array
    {
        return $this->config;
    }

    public static function fromClassName(string $class)
    {
        return new static(['__class' => $class]);
    }

    /**
     * @param array $config
     * @param array $params
     */
    public function resolve(ContainerInterface $container, array $params = [])
    {
        $config = $this->config;

        if (empty($config['__class'])) {
            throw new NotInstantiableException(var_export($config, true));
        }

        $class = $config['__class'];
        if ($container->has($class) && !$container->alreadyBuilding($class)) {
            $container->getDefinition($class)->merge($config)->resolve($container, $params);
        }

        if (!empty($params)) {
            $config['__construct()'] = array_merge($config['__construct()'] ?? [], $params);
        }

        return $this->buildFromArray($container, $config);
    }

    private function buildFromArray(ContainerInterface $container, array $config)
    {
        if (empty($config['__class'])) {
            throw new NotInstantiableException(var_export($config, true));
        }
        $class = $config['__class'];
        unset($config['__class']);

        $dependencies = $this->getDependencies($class);

        if (isset($config['__construct()'])) {
            foreach (array_values($config['__construct()']) as $index => $param) {
                if ($param instanceof DefinitionInterface) {
                    $dependencies[$index] = $param;
                } else {
                    $dependencies[$index] = new ValueDefinition($param);
                }
            }
            unset($config['__construct()']);
        }

        $resolved = $container->resolveDependencies($dependencies);
        $object = new $class(...$resolved);
        $this->configure($container, $object, $config);

        return $object;
    }

    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return DefinitionInterface[] the dependencies of the specified class.
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
     * @deprecated Not recommended for explicit use. Added only to support Yii 2.0 behavior.
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
                if ($arguments instanceof DefinitionInterface) {
                    $arguments = $arguments->resolve($container);
                }
                $object->$action = $arguments;
            }
        }

        return $object;
    }
}
