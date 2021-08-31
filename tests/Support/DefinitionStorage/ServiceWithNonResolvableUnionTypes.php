<?php

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithNonResolvableUnionTypes
{
    public function __construct(ServiceWithNonExistingDependency|ServiceWithPrivateConstructor $class)
    {

    }
}
