<?php

namespace Yiisoft\Di;

final class ClassConfigurator
{
    public function getInterfaceConfig(string $interface): ClassConfig
    {
        $reflection = new \ReflectionClass($interface);
        if (!$reflection->isInterface()) {
            throw new \InvalidArgumentException("$interface is not an interface");
        }
        $config = $this->getReflectionConfig($reflection);

        return $config;
    }

    public function getClassConfig(string $class): ClassConfig
    {
        $reflection = new \ReflectionClass($class);
        if ($reflection->isInterface()) {
            throw new \InvalidArgumentException("$class is not a class");
        }
        $config = $this->getReflectionConfig($reflection);

        return $config;
    }

    public function getReflectionConfig(\ReflectionClass $reflection): ClassConfig
    {
        $config = $this->getReflectionClassConfig($reflection);
        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getName();
            $config->methods[$methodName] = $this->getMethodConfig($method);
        }

        return $config;
    }

    public function getReflectionClassConfig(\ReflectionClass $reflection): ClassConfig
    {
        $config = new ClassConfig();
        $config->isInterface = $reflection->isInterface();
        $config->namespace = $reflection->getNamespaceName();
        $config->modifiers = \Reflection::getModifierNames($reflection->getModifiers());
        $config->name = $reflection->getName();
        $config->shortName = $reflection->getShortName();
        $config->parent = $reflection->getParentClass();
        $config->parents = $this->getClassParents($reflection);
        $config->interfaces = $reflection->getInterfaceNames();

        return $config;
    }

    public function getClassParents(\ReflectionClass $reflection): array
    {
        $parents = [];
        while ($parent = $reflection->getParentClass()) {
            $parents[] = $parent->getName();
            $reflection = $parent;
        }

        return $parents;
    }

    public function getMethodConfig(\ReflectionMethod $method): MethodConfig
    {
        $config = new MethodConfig();
        $config->name = $method->getName();
        $config->modifiers = \Reflection::getModifierNames($method->getModifiers());
        $config->hasReturnType = $method->hasReturnType();
        if ($config->hasReturnType) {
            $config->returnType = new TypeConfig();
            $config->returnType->name = $method->getReturnType()->getName();
            $config->returnType->allowsNull = $method->getReturnType()->allowsNull();
        }
        $config->parameters = [];
        foreach ($method->getParameters() as $param) {
            $config->parameters[$param->getName()] = $this->getMethodParameterConfig($param);
        }

        return $config;
    }

    private function getMethodParameterConfig(\ReflectionParameter $param): ParameterConfig
    {
        $config = new ParameterConfig();
        $config->name = $param->getName();
        $config->hasType = $param->hasType();
        if ($param->hasType()) {
            $config->type = new TypeConfig();
            $config->type->name = $param->getType()->getName();
            $config->type->allowsNull = $param->getType()->allowsNull();
        }
        $config->allowsNull = $param->allowsNull();
        $this->getDefaultValues($param, $config);

        return $config;
    }

    private function getDefaultValues(\ReflectionParameter $param, ParameterConfig $config): void
    {
        $config->isDefaultValueAvailable = $param->isDefaultValueAvailable();
        if ($config->isDefaultValueAvailable) {
            $config->isDefaultValueConstant = $param->isDefaultValueConstant();
            if ($config->isDefaultValueConstant) {
                $config->defaultValueConstantName = $param->getDefaultValueConstantName();
            } else {
                $config->defaultValue = $param->getDefaultValue();
            }
        }
    }
}
