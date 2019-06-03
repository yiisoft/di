<?php


namespace yii\di\definitions;

use yii\di\contracts\Definition;
use yii\di\exceptions\InvalidConfigException;
use yii\di\Reference;

/**
 * Class Definition represents a definition in a container
 */
class Normalizer
{
    /**
     * Definition may be defined multiple ways.
     * Interface name as string:
     *
     * ```php
     * $container->set('interface_name', EngineInterface::class);
     * ```
     *
     * A closure:
     *
     * ```php
     * $container->set('closure', function($container) {
     *     return new MyClass($container->get('db'));
     * });
     * ```
     *
     * A callable array:
     *
     * ```php
     * $container->set('static_call', [MyClass::class, 'create']);
     * ```
     *
     * A definition array:
     *
     * ```php
     * $container->set('full_definition', [
     *     '__class' => EngineMarkOne::class,
     *     '__construct()' => [42],
     *     'argName' => 'value',
     *     'setX()' => [42],
     * ]);
     * ```
     *
     * @param mixed $config
     * @param string $id
     * @throws InvalidConfigException
     */
    public static function normalize($config, string $id = null): Definition
    {
        if ($config instanceof Definition) {
            return $config;
        }

        if (\is_string($config)) {
            return Reference::to($config);
        }

        if (\is_array($config)
            && !(isset($config[0], $config[1]) && count($config) === 2)
        ) {
            if ($id && empty($config['__class'])) {
                $config['__class'] = $id;
            }
            return new ArrayDefinition($config);
        }

        if (\is_callable($config)) {
            return new CallableDefinition($config);
        }

        if (\is_object($config)) {
            return new ValueDefinition($config);
        }

        throw new InvalidConfigException('Invalid definition:' . var_export($config, true));
    }
}
