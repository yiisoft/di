<?php
namespace yii\di\definitions;

use Psr\Container\ContainerInterface;
use yii\di\contracts\Definition;
use yii\di\exceptions\NotInstantiableException;

/**
 * Builds object by array config.
 * @package yii\di
 */
class ArrayDefinition implements Definition
{
    private $class;
    private $params;
    private $config;

    /**
     * @param string $class class name, must not be empty
     * @param array $params
     * @param array $config
     */
    public function __construct(string $class, array $params = [], array $config = [])
    {
        if (empty($class)) {
            throw Exception('class name not given');
        }
        $this->class  = $class;
        $this->params = $params;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    private const CLASS_KEY = '__class';
    private const PARAMS_KEY = '__construct()';

    /**
     * @param string $class class name
     * @param array $params
     * @param array $config
     * @return self
     */
    public static function fromArray(string $class = null, array $params = [], array $config = []): self
    {
        $class  = $config[self::CLASS_KEY] ?? $class;
        $params = $config[self::PARAMS_KEY] ?? $params;

        unset($config[self::CLASS_KEY]);
        unset($config[self::PARAMS_KEY]);

        if (empty($class)) {
            throw new NotInstantiableException(var_export($config, true));
        }

        return new static($class, $params, $config);
    }

    public function resolve(ContainerInterface $container, array $params = [])
    {
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }

        return $this->getBuilder()->build($container, $this);
    }

    private static $builder;

    private function getBuilder()
    {
        if (static::$builder === null) {
            static::$builder = new ArrayBuilder();
        }

        return static::$builder;
    }
}
