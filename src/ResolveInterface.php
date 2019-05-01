<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Psr\Container\ContainerInterface;

/**
 * Interface of references
 *
 * @author Andreas Prucha (Abexto - Helicon Software Development) <andreas.prucha@gmail.com>
 */
interface ResolveInterface
{

    /**
     * Returns the actual value
     * @param ContainerInterface $container Container to use to resolve the reference
     */
    public function get(?ContainerInterface $container = null);

    /**
     * Returns wether this is a valid reference
     * @return bool
     */
    public function isDefined();
}
