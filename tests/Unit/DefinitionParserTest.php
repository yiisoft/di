<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Di\DefinitionParser;
use Yiisoft\Di\Tests\Support\EngineMarkOne;
use Yiisoft\Di\Tests\Support\StaticFactory;
use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Exception\InvalidConfigException;

final class DefinitionParserTest extends TestCase
{
    public function testParseCallableDefinition(): void
    {
        $fn = static fn () => new EngineMarkOne();
        $definition = [
            'definition' => $fn,
            'tags' => ['one', 'two'],
        ];
        [$definition, $meta] = (new DefinitionParser(['tags']))->parse($definition);
        $this->assertSame($fn, $definition);
        $this->assertSame(['tags' => ['one', 'two']], $meta);
    }

    public function testParseArrayCallableDefinition(): void
    {
        $definition = [
            'definition' => [StaticFactory::class, 'create'],
            'tags' => ['one', 'two'],
        ];
        [$definition, $meta] = (new DefinitionParser(['tags']))->parse($definition);
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
        [$definition, $meta] = (new DefinitionParser(['tags']))->parse($definition);
        $this->assertSame([
            EngineMarkOne::class,
            [42],
            [],
            DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA => true,
        ], $definition);
        $this->assertSame(['tags' => ['one', 'two']], $meta);
    }

    public function testErrorOnMethodTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "setId" is not allowed. Did you mean "setId()" or "$setId"?'
        );

        (new DefinitionParser([]))->parse([
            'class' => EngineMarkOne::class,
            'setId' => [42],
        ]);
    }

    public function testErrorOnPropertyTypo(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );
        (new DefinitionParser([]))->parse([
            'class' => EngineMarkOne::class,
            'dev' => true,
        ]);
    }

    public function testErrorOnDisallowMeta(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: metadata "dev" is not allowed. Did you mean "dev()" or "$dev"?'
        );
        (new DefinitionParser(['tags']))->parse([
            'class' => EngineMarkOne::class,
            'tags' => ['a', 'b'],
            'dev' => 42,
        ]);
    }
}
