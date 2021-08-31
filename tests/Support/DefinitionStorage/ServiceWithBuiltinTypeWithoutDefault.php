<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support\DefinitionStorage;

final class ServiceWithBuiltinTypeWithoutDefault
{
    public function __construct(string $test)
    {
    }
}
