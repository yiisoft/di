<?php

namespace Yiisoft\Di\Contracts;

/**
 * Represents service provider that should be deferred to register till services are
 * actually required.
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

    public function provides(): array;
}
