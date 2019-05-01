<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

/**
 * Reference to a callable function
 *
 * The function is called when the reference is resolved by the container.
 *
 * The container is passed to the function as first parameter.
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class CallableReference implements ResolveInterface
{

    /**
     * @var callable
     */
    public $func;

    /**
     * Constructor.
     * @param callable $func
     */
    public function __construct(callable $func)
    {
        $this->func = $func;
    }

    /**
     * Creates an instance of a reference to the given callable
     * @param callable $func
     */
    public static function to(callable $func)
    {
        return new static($func);
    }

    /**
     * Calls the referenced function
     * @param \Psr\Container\ContainerInterface|null $container
     */
    public function get(?\Psr\Container\ContainerInterface $container = null)
    {
        return call_user_func($this->func, $container);
    }

    /**
     * @inheritDoc
     */
    public function isDefined()
    {
        return is_callable($this->func);
    }

}
