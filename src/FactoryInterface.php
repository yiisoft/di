<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use yii\di\exceptions\InvalidConfigException;

/**
 * Factory allows for creation of object using runtime parameters.
 * A factory will try to use a PSR-11 compliant container to get dependencies,
 * but will fall back to manual instantiation
 * if the container cannot provide a required dependency.
 */
interface FactoryInterface
{
    /**
     * Creates a new object using the given configuration and constructor arguments.
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name
     * $object = $factory->createObject(\Yiisoft\Db\Connection::class);
     *
     * // create an object using a configuration array
     * $object = $factory->createObject([
     *     '__class' => \Yiisoft\Db\Connection::class,
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // create an object with two constructor parameters
     * $object = $factory->createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * Using [[Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param string|array|callable $config the object configuration.
     * This can be specified in one of the following forms:
     *
     * - a string: representing the class name of the object to be created
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
     * - a PHP callable: either an anonymous function or an array representing
     *   a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     *
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws InvalidConfigException if the configuration is invalid.
     */
    public function create($config, array $params = []);
}
