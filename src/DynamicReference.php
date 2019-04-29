<?php


namespace yii\di;

use Psr\Container\ContainerInterface;
use yii\di\contracts\Definition;
use yii\di\definitions\Normalizer;

/**
 * Class DynamicReference allows us to define a dependency to a service not defined in the container.
 * This class implements the array configuration syntax common to Yii
 * For example:
 * ```php
 * [
 *    InterfaceA::class => ConcreteA::class,
 *    'alternativeForA' => ConcreteB::class,
 *    Service1::class => [
 *        '__construct()' => [
 *            DynamicReference::to([
 *                '__class' => SomeClass::class,
 *                'someProp' => 15
 *            ]
 *        ]
 *    ]
 * ]
 * ```
 */
class DynamicReference implements Definition
{
    private $definition;

    private function __construct($definition)
    {
        $this->definition = Normalizer::normalize($definition);
    }

    public static function to($definition)
    {
        return new self($definition);
    }

    /**
     * @param Container $container
     */
    public function resolve(ContainerInterface $container)
    {
        return $this->definition->resolve($container);
    }
}
