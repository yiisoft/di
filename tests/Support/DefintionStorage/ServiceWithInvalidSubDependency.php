<?php

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithInvalidSubDependency
{
    public function __construct(ServiceWithNonExistingDependency $serviceWithInvalidDependency)
    {

    }
}
