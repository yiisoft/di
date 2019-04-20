<?php


namespace yii\di;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;
use yii\di\dependencies\NamedDependency;
use yii\di\dependencies\ValueDependency;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotInstantiableException;
use yii\di\resolvers\ClassNameResolver;

/**
 * Class Definition represents a definition in a container
 * @package yii\di
 */
class Definition
{
    private static $dependencies = [];

    private const TYPE_CALLABLE = 'callable';
    private const TYPE_ARRAY = 'array';
    private const TYPE_RESOLVABLE = 'resolvable';
    private const TYPE_VALUE = 'value';
    private $type;

    /**
     * @var array|DependencyInterface
     */
    private $config;

    private function __construct($config, string $type)
    {
        $this->type = $type;
        $this->config = $config;
    }

    public static function normalize($config): self
    {
        if ($config instanceof self) {
            return $config;
        }

        if (is_string($config)) {
            return new self(['__class' => $config], self::TYPE_ARRAY);
        }

        if (is_array($config)
            && !isset($config[0], $config[1])
            && isset($config['__class'])
        ) {
            return new self($config, self::TYPE_ARRAY);
        }

        if (\is_callable($config)) {
            return new self($config, self::TYPE_CALLABLE);
        }

        if ($config instanceof DependencyInterface) {
            return new self($config, self::TYPE_RESOLVABLE);
        }

        if (is_object($config)) {
            return new self($config, self::TYPE_VALUE);
        }

        throw new InvalidConfigException('Invalid definition:' . var_export($config, true));
    }

    /**
     * @param array $definition
     * @param ContainerInterface $rootContainer
     */
    private function resolveArray(ContainerInterface $rootContainer, array $config, array $params)
    {
        if (empty($config['__class'])) {
            throw new NotInstantiableException(var_export($config, true));
        }
        $class = $config['__class'];
        unset($config['__class']);

        $dependencies = $this->getDependencies($class);

        if (isset($config['__construct()'])) {
            foreach (array_values($config['__construct()']) as $index => $param) {
                if (!$param instanceof Reference) {
                    $dependencies[$index] = new ValueDependency($param);
                } else {
                    $dependencies[$index] = new NamedDependency($param->getId(), false);
                }
            }
            unset($config['__construct()']);
        }

        $resolved = [];
        /** @var DependencyInterface $dependency */
        foreach ($dependencies as $dependency) {
            $resolved[] = $dependency->resolve($rootContainer);
        }
        $object = new $class(...$resolved);

        $this->configure($object, $config, $rootContainer);
        return $object;
    }

    /**
     * @param Container $container
     * @return mixed|object
     * @throws NotInstantiableException
     */
    public function resolve(ContainerInterface $rootContainer, array $params = [])
    {
        switch ($this->type) {
            case self::TYPE_CALLABLE:
                return $rootContainer->getInjector()->invoke($this->config, $params);
            case self::TYPE_ARRAY:
                return $this->resolveArray($rootContainer, $this->config, $params);
            case self::TYPE_VALUE:
                return $this->config;
            case self::TYPE_RESOLVABLE:
                return $this->config->resolve($rootContainer, $params);
        }

        throw new \RuntimeException('Attempted to resolve invalid definition of type: ' . $this->type);
    }

    /**
     * Configures an object with the given configuration.
     * @deprecated Not recommended for explicit use. Added only to support Yii 2.0 behavior.
     * @param object $object the object to be configured
     * @param iterable $config property values and methods to call
     * @param ContainerInterface $container
     * @return object the object itself
     */
    private function configure($object, iterable $config, ContainerInterface $container)
    {
        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                \call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                if ($arguments instanceof DependencyInterface) {
                    $arguments = $arguments->resolve($container);
                }
                $object->$action = $arguments;
            }
        }

        return $object;
    }

    /**
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name
     * @return DependencyInterface[] the dependencies of the specified class.
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
}
