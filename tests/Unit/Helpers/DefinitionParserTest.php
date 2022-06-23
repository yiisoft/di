<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Helpers\DefinitionParser;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\StaticFactory;

final class DefinitionParserTest extends TestCase
{
    public function testParseCallableDefinition(): void
    {
        $fn = static fn () => new EngineMarkOne();
        $definition = [
            'definition' => $fn,
            'tags' => ['one', 'two'],
        ];
        [$definition, $meta] = DefinitionParser::parse($definition);
        $this->assertSame($fn, $definition);
        $this->assertSame(['tags' => ['one', 'two']], $meta);
    }

    public function testParseArrayCallableDefinition(): void
    {
        $definition = [
            'definition' => [StaticFactory::class, 'create'],
            'tags' => ['one', 'two'],
        ];
        [$definition, $meta] = DefinitionParser::parse($definition);
        $this->assertSame([StaticFactory::class, 'create'], $definition);
        $this->assertSame(['tags' => ['one', 'two']], $meta);
    }

    public function testParseArrayDefinition(): void
    {
        $definition = [
            'class' => EngineMarkOne::class,
            '__construct()' => [42],
            'tags' => ['one', 'two'],
        ];
        [$definition, $meta] = DefinitionParser::parse($definition);
        $this->assertSame([
            'class' => EngineMarkOne::class,
            '__construct()' => [42],
            'methodsAndProperties' => [],
            DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA => true,
        ], $definition);
        $this->assertSame(['tags' => ['one', 'two']], $meta);
    }
}
