<?php

namespace Yiisoft\Di;

final class MethodConfig
{
    /**
     * @var string[]
     */
    public array $modifiers;

    public string $name;

    /**
     * @var ParameterConfig[]
     */
    public array $parameters;

    public bool $hasReturnType;

    public TypeConfig $returnType;
}
