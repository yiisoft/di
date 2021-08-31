<?php

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\DefinitionStorage;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithBultinTypeWithoutDefault;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithInvalidSubDependency;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithNonExistingDependency;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithPrivateConstructor;


final class DefinitionStorageBuildStackTest extends TestCase
{
    public function testExplicitDefinitionIsNotChecked(): void
    {
        $storage = new DefinitionStorage(['existing' => 'anything']);
        $this->assertTrue($storage->has('existing'));
        $this->assertSame([], $storage->getBuildStack());
    }

    public function testNonExistingService(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(NonExisitng::class));
        $this->assertSame([NonExisitng::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingDependency::class));
        $this->assertSame([ServiceWithNonExistingDependency::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithInvalidSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithInvalidSubDependency::class));
        $this->assertSame([ServiceWithInvalidSubDependency::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithPrivateConstructor(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithPrivateConstructor::class));
        $this->assertSame([ServiceWithPrivateConstructor::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithBuiltInTypeWithoutDefault(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithBultinTypeWithoutDefault::class));
        $this->assertSame([ServiceWithBultinTypeWithoutDefault::class => 1], $storage->getBuildStack());
    }
}
