<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Exception\InvalidConfigException;

use function is_array;
use function is_callable;

/**
 * @internal Splits metadata and definition.
 *
 * Supports the following configuration:
 *
 * 1) With a dedicated definition:
 *
 * ```php
 * Engine::class => [
 *     'definition' => [
 *         '__class' => BigEngine::class,
 *         'setNumber()' => [42],
 *     ],
 *     'tags' => ['a', 'b'],
 *     'reset' => function () {
 *         $this->number = 42;
 *      },
 * ]
 * ```
 *
 * 2) Mixed in array definition:
 *
 * ```php
 * Engine::class => [
 *     '__class' => BigEngine::class,
 *     'setNumber()' => [42],
 *     'tags' => ['a', 'b'],
 *     'reset' => function () {
 *         $this->number = 42;
 *      },
 * ]
 * ```
 */
final class DefinitionParser
{
    private const DEFINITION_META = 'definition';

    public const IS_PREPARED_ARRAY_DEFINITION_DATA = 'isPreparedArrayDefinitionData';

    /**
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     */
    public static function parse($definition): array
    {
        if (!is_array($definition)) {
            return [$definition, []];
        }

        // Dedicated definition
        if (isset($definition[self::DEFINITION_META])) {
            $newDefinition = $definition[self::DEFINITION_META];
            unset($definition[self::DEFINITION_META]);

            return [$newDefinition, $definition];
        }

        // Callable definition
        if (is_callable($definition, true)) {
            return [$definition, []];
        }

        // Array definition
        $meta = [];
        $class = null;
        $constructorArguments = [];
        $methodsAndProperties = [];
        foreach ($definition as $key => $value) {
            // Class
            if ($key === ArrayDefinition::CLASS_NAME) {
                $class = $value;
                continue;
            }

            // Constructor arguments
            if ($key === ArrayDefinition::CONSTRUCTOR) {
                $constructorArguments = $value;
                continue;
            }

            // Methods and properties
            if (substr($key, -2) === '()') {
                $methodsAndProperties[$key] = [ArrayDefinition::TYPE_METHOD, $key, $value];
                continue;
            }
            if (strncmp($key, '$', 1) === 0) {
                $methodsAndProperties[$key] = [ArrayDefinition::TYPE_PROPERTY, $key, $value];
                continue;
            }

            $meta[$key] = $value;
        }
        return [
            [
                $class,
                $constructorArguments,
                $methodsAndProperties,
                self::IS_PREPARED_ARRAY_DEFINITION_DATA => true,
            ],
            $meta,
        ];
    }
}
