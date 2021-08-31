<?php

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithNonExistingDependency
{
    public function __construct(NonExisting $nonExisting)
    {

    }
}
