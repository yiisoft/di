<?php

namespace Yiisoft\Di;

final class ClassConfig
{
    public bool $isInterface;

    public string $namespace;

    /**
     * @var string[]
     */
    public array $modifiers;

    public string $name;

    public string $shortName;

    public string $parent;

    /**
     * @var string[]
     */
    public array $parents;

    /**
     * @var string[]
     */
    public array $interfaces;

    /**
     * @var MethodConfig[]
     */
    public array $methods;
}
