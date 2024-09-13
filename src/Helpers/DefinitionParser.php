<?php

declare(strict_types=1);

namespace Yiisoft\Di\Helpers;

use Yiisoft\Definitions\ArrayDefinition;

use function count;
use function is_array;
use function is_callable;
use function is_string;

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
     * @param mixed $definition Definition to parse.
     *
     * @return array Definition parsed into an array of a special structure.
     * @psalm-return array{mixed,array}
     */
    public static function parse(mixed $definition): array
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
            if (is_string($key)) {
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
                if (count($methodArray = explode('()', $key, 2)) === 2) {
                    $methodsAndProperties[$key] = [ArrayDefinition::TYPE_METHOD, $methodArray[0], $value];
                    continue;
                }
                if (count($propertyArray = explode('$', $key, 2)) === 2) {
                    $methodsAndProperties[$key] = [ArrayDefinition::TYPE_PROPERTY, $propertyArray[1], $value];
                    continue;
                }
            }

            $meta[$key] = $value;
        }
        return [
            [
                'class' => $class,
                '__construct()' => $constructorArguments,
                'methodsAndProperties' => $methodsAndProperties,
                self::IS_PREPARED_ARRAY_DEFINITION_DATA => true,
            ],
            $meta,
        ];
    }
}
