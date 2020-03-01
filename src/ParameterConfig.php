<?php

namespace Yiisoft\Di;

final class ParameterConfig
{
    public bool $hasType;

    public TypeConfig $type;

    public string $name;

    public bool $allowsNull;

    public bool $isDefaultValueAvailable;

    public bool $isDefaultValueConstant;

    public string $defaultValueConstantName;

    public $defaultValue;
}
