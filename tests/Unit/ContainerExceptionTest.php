<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Di\ContainerException;

final class ContainerExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $exception = new ContainerException('test', new RuntimeException('i am angry'));

        $this->assertSame(
            'Caught unhandled error "i am angry" while checking if container has "test".',
            $exception->getMessage(),
        );
        $this->assertSame('Unable to check if container has "test" ID.', $exception->getName());
        $this->assertSame(
            <<<SOLUTION
            Ensure delegated containers handle "test" ID in `has()` without throwing unexpected errors.
            SOLUTION,
            $exception->getSolution(),
        );
    }

    public function testEmptyMessage(): void
    {
        $exception = new ContainerException('test', new RuntimeException());

        $this->assertSame(
            'Caught unhandled error "RuntimeException" while checking if container has "test".',
            $exception->getMessage(),
        );
    }

    public function testCode(): void
    {
        $exception = new ContainerException('test', new RuntimeException());

        $this->assertSame(0, $exception->getCode());
    }
}
