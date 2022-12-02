<?php

declare(strict_types=1);

namespace Yiisoft\Di\Helpers;

use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\Normalizer;
use Yiisoft\Di\ExtensibleService;

use function is_array;

/**
 * @internal Normalizes a definition.
 */
final class DefinitionNormalizer
{
    /**
     * @param mixed $definition Definition to normalize.
     * @param string $id Service ID.
     *
     * @throws InvalidConfigException If configuration is not valid.
     */
    public static function normalize(mixed $definition, string $id): DefinitionInterface
    {
        if (is_array($definition) && isset($definition[DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA])) {
            /** @psalm-suppress MixedArgument Definition should be valid {@see Container::$validate} */
            return ArrayDefinition::fromPreparedData(
                $definition['class'] ?? $id,
                $definition['__construct()'],
                $definition['methodsAndProperties']
            );
        }

        if ($definition instanceof ExtensibleService) {
            return $definition;
        }

        return Normalizer::normalize($definition, $id);
    }
}
