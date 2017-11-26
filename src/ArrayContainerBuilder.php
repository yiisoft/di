<?php

namespace yii\di;


class ArrayContainerBuilder
{
    private $container;

    public function __construct($defintions = [], $parent = null)
    {
        $this->container = new Container([], $parent);
        if ($defintions !== []) {
            $this->configure($defintions);
        }
    }

    public function set(string $id, $definition)
    {
        if (is_string($definition) && strpos($definition, '@') === 0) {
            $this->container->setAlias($id, substr($definition, 1));
            return;
        }

        if (is_array($definition) && !isset($definition[0], $definition[1])) {
            $definition = $this->buildDefintion($definition);
        }
        $this->container->set($id, $definition);
    }

    /**
     * @param $argument
     * @return Reference
     */
    protected function replaceReference($argument)
    {
        if (is_string($argument) && strpos($argument, '@') === 0) {
            $argument = new Reference(substr($argument, 1));
        }
        return $argument;
    }

    protected function buildDefintion(array $config): ClassDefinition
    {
        if (!isset($config['__class'])) {
            throw new InvalidConfigException("__class missing in defintion.");
        }

        $definition = new ClassDefinition($config['__class']);
        unset($config['__class']);

        if (isset($config['__construct()'])) {
            array_walk_recursive($config['__construct()'], function(&$argument) {
                $argument = $this->replaceReference($argument);
            });

            $definition->setArguments($config['__construct()']);
            unset($config['__construct()']);
        }

        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                array_walk_recursive($arguments, function(&$argument) {
                    $argument = $this->replaceReference($argument);
                });
                $definition->call(substr($action, 0, -2), $arguments);
            } else {
                // property
                $definition->setProperty($action, $this->replaceReference($arguments));
            }
        }

        return $definition;
    }

    public function configure(array $defintions)
    {
        foreach ($defintions as $id => $defintion) {
            $this->set($id, $defintion);
        }
    }

    public function build(): Container
    {
        return $this->container;
    }
}