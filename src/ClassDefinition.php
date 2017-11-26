<?php

namespace yii\di;

use Psr\Container\ContainerInterface;
use ReflectionClass;

class ClassDefinition
{
    private const ACTION_PROPERTY = 'property';
    private const ACTION_CALL = 'call';

    private $class;
    private $actions = [];
    private $arguments = [];

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function call(string $name, array $arguments = []): self
    {
        $this->actions[] = [self::ACTION_CALL, $name, $arguments];
        return $this;
    }

    public function setProperty(string $name, $value): self
    {
        $this->actions[] = [self::ACTION_PROPERTY, $name, $value];
        return $this;
    }

    public function __invoke(ContainerInterface $container)
    {
        $reflection = new ReflectionClass($this->class);

        $arguments = $this->arguments;
        array_walk_recursive($arguments, function (&$argument) use ($container) {
            if ($argument instanceof Reference) {
                $argument = $container->get($argument->getId());
            }
        });
        $object = $reflection->newInstanceArgs($arguments);

        foreach ($this->actions as [$type, $name, $value]) {
            switch ($type) {
                case self::ACTION_CALL:
                    array_walk_recursive($value, function (&$argument) use ($container) {
                        if ($argument instanceof Reference) {
                            $argument = $container->get($argument->getId());
                        }
                    });
                    call_user_func_array([$object, $name], $value);
                    break;
                case self::ACTION_PROPERTY:
                    if ($value instanceof Reference) {
                        $value = $container->get($value->getId());
                    }

                    $object->$name = $value;
                    break;
            }
        }

        return $object;
    }
}
