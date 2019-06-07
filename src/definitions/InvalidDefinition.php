<?php
namespace yii\di\definitions;

use Psr\Container\ContainerInterface;
use yii\di\contracts\Definition;
use yii\di\exceptions\NotFoundException;

/**
 * An invalid dependency is created when a parameter has no type and no default value.
 * For example:
 * ```php
 * public function __construct($a, $b) {}
 * ```
 *
 * These dependency must be replaced, attempting to resolve them will throw an exception
 */
class InvalidDefinition implements Definition
{
    public function resolve(ContainerInterface $container, array $params = [])
    {
        throw new NotFoundException('Invalid reference');
    }
}
