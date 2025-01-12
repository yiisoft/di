<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Di\BuildingException;

final class BuildingExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $exception = new BuildingException('test', new RuntimeException('i am angry'));

        $this->assertSame('Caught unhandled error "i am angry" while building "test".', $exception->getMessage());
        $this->assertSame('Couldn\'t build requested object.', $exception->getName());
        $this->assertSame('See (https://github.com/yiisoft/di)[https://github.com/yiisoft/di] for more documentation.', $exception->getSolution());
    }

    public function testEmptyMessage(): void
    {
        $exception = new BuildingException('test', new RuntimeException());

        $this->assertSame('Caught unhandled error "RuntimeException" while building "test".', $exception->getMessage());
    }

    public function testBuildStack(): void
    {
        $exception = new BuildingException('test', new RuntimeException('i am angry'), ['a', 'b', 'test']);

        $this->assertSame('Caught unhandled error "i am angry" while building "a" -> "b" -> "test".', $exception->getMessage());
    }

    public function testCode(): void
    {
        $exception = new BuildingException('test', new RuntimeException());

        $this->assertSame(0, $exception->getCode());
    }
}
