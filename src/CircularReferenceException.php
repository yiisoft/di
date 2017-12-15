<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerExceptionInterface;

/**
 * CircularReferenceException is thrown when DI configuration
 * contains self-references of any level and thus could not
 * be resolved.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class CircularReferenceException extends \Exception implements ContainerExceptionInterface
{
    
}