<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Exception\InvalidConfigException;

use function in_array;
use function is_array;
use function is_string;

/**
 * @internal Split metadata and definition
 *
 * Support configuration:
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
            return [$definition, []];
        }

        // Dedicated definition
        if (isset($definition[self::DEFINITION_META])) {
            $newDefinition = $definition[self::DEFINITION_META];
            unset($definition[self::DEFINITION_META]);
            foreach ($definition as $key => $_value) {
                $this->checkMetaKey($key);
            }
            return [$newDefinition, $definition];
        }

        $meta = [];
        foreach ($definition as $key => $value) {
            // It is not array definition
            if (!is_string($key)) {
                break;
            }

            // Array definition keys
            if (
                $key === ArrayDefinition::CLASS_NAME ||
                $key === ArrayDefinition::CONSTRUCTOR ||
                substr($key, -2) === '()' ||
                strncmp($key, '$', 1) === 0
            ) {
                continue;
            }

            $this->checkMetaKey($key);

            $meta[$key] = $value;
            unset($definition[$key]);
        }

        return [$definition, $meta];
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
