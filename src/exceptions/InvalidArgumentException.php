<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * Invalid argument used for a container related method call
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements ContainerExceptionInterface
{

}
