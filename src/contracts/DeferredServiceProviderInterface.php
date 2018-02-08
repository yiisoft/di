<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\contracts;

/**
 * Represents service provider that should be deferred to register till services are
 * actually required.
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 * @since 1.0
 */
interface DeferredServiceProviderInterface extends ServiceProviderInterface
{
    /**
     * Identifies whether service provider would register definition for
     * given identifier or not.
     *
     * @param string $id class, interface or identifier in the Container.
     * @return bool whether service provider would register definition or not.
     */
    public function hasDefinitionFor(string $id): bool;
}
