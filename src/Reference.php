<?php


namespace yii\di;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;

/**
 * Class Reference allows us to define a dependency to a service in the container in another service definition.
 * For example:
 * ```php
 * [
 *    InterfaceA::class => ConcreteA::class,
 *    'alternativeForA' => ConcreteB::class,
 *    Service1::class => [
 *        '__construct()' => [
 *            Reference::to('alternativeForA')
 *        ]
 *    ]
 * ]
 * ```
 */
class Reference implements DependencyInterface
{
    private $id;

    private function __construct($id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function to(string $id)
    {
        return new self($id);
    }

    /**
     * @param Container $container
     */
    public function resolve(ContainerInterface $container)
    {
        return $container->get($this->id);
    }
}
