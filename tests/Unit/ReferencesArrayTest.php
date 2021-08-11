<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\ReferencesArray;
use Yiisoft\Di\DynamicReferencesArray;
use Yiisoft\Factory\Definition\Reference;
use Yiisoft\Factory\Definition\DynamicReference;
use Yiisoft\Factory\Exception\InvalidConfigException;

class ReferencesArrayTest extends TestCase
{
    public function testReferencesArray()
    {
        $ids = ['key1' => 'first', 'key2' => 'second'];

        $references = ReferencesArray::from($ids);

        $this->assertInstanceOf(Reference::class, $references['key1']);
        $this->assertSame('first', $references['key1']->getId());
        $this->assertInstanceOf(Reference::class, $references['key2']);
        $this->assertSame('second', $references['key2']->getId());
    }

    public function testReferencesArrayFail()
    {
        $ids = ['first', 22];

        $this->expectException(InvalidConfigException::class);
        $references = ReferencesArray::from($ids);
    }

    public function testDynamicReferencesArray()
    {
        $ids = ['key1' => 'first', 'key2' => 'second'];

        $references = DynamicReferencesArray::from($ids);

        $this->assertInstanceOf(DynamicReference::class, $references['key1']);
        $this->assertInstanceOf(DynamicReference::class, $references['key2']);
    }

    public function testDynamicReferencesArrayFail()
    {
        $ids = ['first', 22];

        $this->expectException(InvalidConfigException::class);
        $references = DynamicReferencesArray::from($ids);
    }
}
