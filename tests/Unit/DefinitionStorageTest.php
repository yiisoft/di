<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\DefinitionStorage;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithBuiltinTypeWithoutDefault;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithNonExistingSubDependency;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithNonExistingDependency;
use Yiisoft\Di\Tests\Support\DefinitionStorage\ServiceWithPrivateConstructor;

final class DefinitionStorageTest extends TestCase
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
        $this->assertFalse($storage->has(\NonExisitng::class));
        $this->assertSame([\NonExisitng::class => 1], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingDependency::class => 1,
                \NonExisting::class => 1,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithNonExistingSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingSubDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingSubDependency::class => 1,
                ServiceWithNonExistingDependency::class => 1,
                \NonExisting::class => 1,
            ],
            $storage->getBuildStack()
        );
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
        $this->assertFalse($storage->has(ServiceWithBuiltinTypeWithoutDefault::class));
        $this->assertSame([ServiceWithBuiltinTypeWithoutDefault::class => 1], $storage->getBuildStack());
    }
}
