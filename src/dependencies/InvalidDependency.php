<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\dependencies;

use Psr\Container\ContainerInterface;
use yii\di\contracts\DependencyInterface;
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
class InvalidDependency implements DependencyInterface
{
    public function resolve(ContainerInterface $container)
    {
        throw new NotFoundException('Invalid reference');
    }
}
