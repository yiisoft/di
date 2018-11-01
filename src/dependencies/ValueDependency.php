<?php


namespace yii\di\dependencies;


use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;

class ValueDependency implements DependencyInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param ContainerInterface $container
     */
    public function resolve(ContainerInterface $container)
    {
        return $this->value;
    }

    /**
     * This is used to detect circular reference.
     * If a concrete reference is guaranteed to never be part of such a circle
     * (for example because it references a simple value) NULL should be returned
     * @return string|null A string uniquely identifying a service in the container
     */
    public function getId(): ?string
    {
        return null;
    }
}