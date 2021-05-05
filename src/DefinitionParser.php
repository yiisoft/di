<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use League\Container\Definition\DefinitionInterface;
use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Definition\ArrayDefinitionValidator;
use Yiisoft\Factory\Exception\InvalidConfigException;

use function in_array;
use function is_array;
use function is_object;
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

    private array $allowedMeta;

    public function __construct(array $allowedMeta)
    {
        $this->allowedMeta = $allowedMeta;
    }

    /**
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     */
    public function parse($definition): array
    {
        if (!is_array($definition)) {
            $this->checkNotArrayDefinitionConfig($definition);
            return [$definition, []];
        }

        // Dedicated definition
        if (isset($definition[self::DEFINITION_META])) {
            $newDefinition = $definition[self::DEFINITION_META];
            unset($definition[self::DEFINITION_META]);

            foreach ($definition as $key => $_value) {
                $this->checkMetaKey($key);
            }

            if (is_array($newDefinition)) {
                $this->prepareDefinitionFromArray($newDefinition);
            } else {
                $this->checkNotArrayDefinitionConfig($newDefinition);
            }

            return [$newDefinition, $definition];
        }

        $meta = [];
        $this->prepareDefinitionFromArray($definition, $meta);
        return [$definition, $meta];
    }

    /**
     * @throws InvalidConfigException
     */
    private function prepareDefinitionFromArray(array &$definition, array &$meta = null): void
    {
        $result = [
            ArrayDefinition::IS_PREPARED_CONFIG => true,
            ArrayDefinition::METHODS_AND_PROPERTIES => [],
        ];
        foreach ($definition as $key => $value) {
            // It is not array definition
            if (!is_string($key)) {
                $this->checkNotArrayDefinitionConfig($definition);
                return;
            }

            // Class
            if ($key === ArrayDefinition::CLASS_NAME) {
                ArrayDefinitionValidator::validateClassName($value);
                $result[$key] = $value;
                continue;
            }

            // Constructor arguments
            if ($key === ArrayDefinition::CONSTRUCTOR) {
                ArrayDefinitionValidator::validateConstructorArguments($value);
                $result[$key] = $value;
                continue;
            }

            // Methods and properties
            if (substr($key, -2) === '()') {
                ArrayDefinitionValidator::validateMethodArguments($value);
                $result[ArrayDefinition::METHODS_AND_PROPERTIES][$key] = [ArrayDefinition::FLAG_METHOD, $key, $value];
                continue;
            }
            if (strncmp($key, '$', 1) === 0) {
                $result[ArrayDefinition::METHODS_AND_PROPERTIES][$key] = [ArrayDefinition::FLAG_PROPERTY, $key, $value];
                continue;
            }

            $this->checkMetaKey($key);

            if ($meta !== null) {
                $meta[$key] = $value;
            }
        }
        $definition = $result;
    }

    /**
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     */
    private function checkNotArrayDefinitionConfig($definition): void
    {
        if ($definition instanceof DefinitionInterface) {
            return;
        }

        if (is_array($definition)) {
            return;
        }

        if (is_string($definition) && !empty($definition)) {
            return;
        }

        if (is_object($definition)) {
            return;
        }

        throw new InvalidConfigException('Invalid definition:' . var_export($definition, true));
    }

    /**
     * @throws InvalidConfigException
     */
    private function checkMetaKey(string $key): void
    {
        if (!in_array($key, $this->allowedMeta, true)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: metadata "%s" is not allowed. Did you mean "%s()" or "$%s"?',
                    $key,
                    $key,
                    $key,
                )
            );
        }
    }
}
