<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Reference;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Di\Reference\TagReference;

final class TagReferenceTest extends TestCase
{
    public function testConstructorIsPrivate(): void
    {
        $this->expectException(Error::class);
        new TagReference();
    }

    public function testConstructor(): void
    {
        $reflection = new \ReflectionClass(TagReference::class);
        $reflectionMethod = $reflection->getConstructor();
        $this->assertTrue($reflectionMethod->isPrivate());
        if (PHP_VERSION_ID < 81000) {
            $reflectionMethod->setAccessible(true);
        }

        $reflectionMethod->invoke($reflection->newInstanceWithoutConstructor());
    }

    public function testAliases(): void
    {
        $this->assertFalse(TagReference::isTagAlias('test'));
        $this->assertFalse(TagReference::isTagAlias('tag#test'));
        $this->assertTrue(TagReference::isTagAlias('tag@test'));
    }

    public function testExtractTag(): void
    {
        $this->assertEquals('test', TagReference::extractTagFromAlias('tag@test'));
    }

    public function testExtractWrongTagDelimiter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TagReference::extractTagFromAlias('tag#test');
    }

    public function testExtractWrongTagFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TagReference::extractTagFromAlias('test');
    }

    public function testReference(): void
    {
        $reference = TagReference::to('test');
        $spyContainer = new class () implements ContainerInterface {
            public function get($id)
            {
                return $id;
            }

            public function has($id): bool
            {
                return true;
            }
        };

        $result = $reference->resolve($spyContainer);

        $this->assertEquals('tag@test', $result);
    }
}
