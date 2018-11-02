<?php


namespace yii\di;

use yii\di\contracts\DependencyInterface;
use yii\di\dependencies\NamedDependency;
use yii\di\dependencies\ValueDependency;
use yii\di\exceptions\InvalidConfigException;
use yii\di\exceptions\NotInstantiableException;

/**
 * Class Definition represents a definition in a container
 * @package yii\di
 */
class Definition
{
    private const TYPE_CALLABLE = 'callable';
    private const TYPE_ARRAY = 'array';
    private const TYPE_RESOLVABLE = 'resolvable';
    private const TYPE_VALUE = 'value';
    private $type;
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
     * @param AbstractContainer $container
     */
    private function resolveArray(array $config, AbstractContainer $container)
    {
        if (empty($config['__class'])) {
            throw new NotInstantiableException(var_export($config, true));
        }
        $class = $config['__class'];
        unset($config['__class']);

        $dependencies = $container->getDependencies($class);


        if (isset($config['__construct()'])) {
            foreach (array_values($config['__construct()']) as $index => $param) {
                if (!$param instanceof Reference) {
                    $dependencies[$index] = new ValueDependency($param);
                } else {
                    $dependencies[$index] = new NamedDependency($container->dereference($param), false);
                }
            }
            unset($config['__construct()']);
        }


        $object = $container->createFromDependencies($class, $dependencies);

        $this->configure($object, $config, $container);
        return $object;
    }

    /**
     * @param AbstractContainer $container
     * @return mixed|object
     * @throws NotInstantiableException
     */
    public function resolve(AbstractContainer $container)
    {
        switch ($this->type) {
            case self::TYPE_CALLABLE:
                return call_user_func($this->config, $container);
            case self::TYPE_ARRAY:
                return $this->resolveArray($this->config, $container);
            case self::TYPE_VALUE:
                return $this->config;
            case self::TYPE_RESOLVABLE:
                return $this->config->resolve($container);
        }

        throw new \RuntimeException('Attempted to resolve invalid definition of type: ' . $this->type);
    }

    /**
     * Configures an object with the given configuration.
     * @deprecated Not recommended for explicit use. Added only to support Yii 2.0 behavior.
     * @param object $object the object to be configured
     * @param iterable $config property values and methods to call
     * @return object the object itself
     */
    private function configure($object, iterable $config, AbstractContainer $container)
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
}