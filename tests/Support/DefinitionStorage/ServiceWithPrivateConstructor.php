<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithPrivateConstructor
{
    private function __construct()
    {
    }
}
