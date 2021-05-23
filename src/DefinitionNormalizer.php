<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Factory\Definition\ArrayDefinition;
use Yiisoft\Factory\Definition\DefinitionInterface;
use Yiisoft\Factory\Definition\Normalizer;
use Yiisoft\Factory\Exception\InvalidConfigException;
use function is_array;

/**
 * @internal
 */
final class DefinitionNormalizer
{
    /**
     * @param mixed $definition
     *
     * @throws InvalidConfigException
     */
    public static function normalize($definition, string $id = null): DefinitionInterface
    {
        if (is_array($definition) && isset($definition[DefinitionParser::IS_PREPARED_ARRAY_DEFINITION_DATA])) {
            [$class, $constructorArguments, $methodsAndProperties] = $definition;

            $class = $class ?? $id;
            if ($class === null) {
                throw new InvalidConfigException('Invalid definition: don\'t set class name.');
            }

            return ArrayDefinition::fromPreparedData($class, $constructorArguments, $methodsAndProperties);
        }

        return Normalizer::normalize($definition, $id);
    }
}
