<?php

declare(strict_types=1);

namespace Yiisoft\Di;

use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Infrastructure\Normalizer;

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
            $class = $definition['class'];
            $constructorArguments = $definition['__construct()'];
            $methodsAndProperties = $definition['methodsAndProperties'];

            $class = $class ?? $id;
            if ($class === null) {
                throw new InvalidConfigException('Invalid definition: don\'t set class name.');
            }

            return ArrayDefinition::fromPreparedData($class, $constructorArguments, $methodsAndProperties);
        }

        if ($definition instanceof ExtensibleService) {
            return $definition;
        }

        return Normalizer::normalize($definition, $id);
    }
}
