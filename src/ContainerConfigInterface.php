<?php

namespace Yiisoft\Di;

/**
 * Container configuration.
 */
interface ContainerConfigInterface
{
    /**
     * @return array Service providers to get definitions from.
     */
    public function getProviders(): array;

    /**
     * @return array Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function getTags(): array;

    /**
     * @return bool Whether definitions should be validated immediately.
     */
    public function shouldValidate(): bool;

    /**
     * @return array Container delegates. Each delegate is a callable in format
     * "function (ContainerInterface $container): ContainerInterface". The container instance returned is used
     * in case a service can not be found in primary container.
     */
    public function getDelegates(): array;
}
