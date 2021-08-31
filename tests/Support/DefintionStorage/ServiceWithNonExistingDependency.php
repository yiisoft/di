<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithNonExistingDependency
{
    public function __construct(NonExisting $nonExisting)
    {
    }
}
