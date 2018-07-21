<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no entry was found in the container.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{

}
