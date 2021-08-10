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
        $ids = ['first', 'second'];

        $references = ReferencesArray::from($ids);

        $this->assertInstanceOf(Reference::class, $references[0]);
        $this->assertSame('first', $references[0]->getId());
        $this->assertInstanceOf(Reference::class, $references[1]);
        $this->assertSame('second', $references[1]->getId());
    }

    public function testReferencesArrayFail()
    {
        $ids = ['first', 22];

        $this->expectException(InvalidConfigException::class);
        $references = ReferencesArray::from($ids);
    }

    public function testDynamicReferencesArray()
    {
        $ids = ['first', 'second'];

        $references = DynamicReferencesArray::from($ids);

        $this->assertInstanceOf(DynamicReference::class, $references[0]);
        $this->assertInstanceOf(DynamicReference::class, $references[1]);
    }

    public function testDynamicReferencesArrayFail()
    {
        $ids = ['first', 22];

        $this->expectException(InvalidConfigException::class);
        $references = DynamicReferencesArray::from($ids);
    }
}
