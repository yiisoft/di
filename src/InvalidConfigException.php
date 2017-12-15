<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerExceptionInterface;

/**
 * InvalidConfigException is thrown when configuration passed to
 * container is not valid.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class InvalidConfigException extends \Exception implements ContainerExceptionInterface
{

}