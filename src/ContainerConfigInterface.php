<?php

declare(strict_types=1);

namespace Yiisoft\Di;

/**
 * Container configuration.
 */
interface ContainerConfigInterface
{
    /**
     * @return array Definitions to put into container.
     */
    public function getDefinitions(): iterable;

    /**
     * @return iterable Service providers to get definitions from.
     */
    public function getProviders(): iterable;

    /**
     * @return iterable Tagged service IDs. The structure is `['tagID' => ['service1', 'service2']]`.
     */
    public function getTags(): iterable;

    /**
     * @return bool Whether definitions should be validated immediately.
     */
    public function shouldValidate(): bool;

    /**
     * @return iterable Container delegates. Each delegate is a callable in format
     * `function (ContainerInterface $container): ContainerInterface`. The container instance returned is used
     * in case a service can not be found in primary container.
     */
    public function getDelegates(): iterable;

    /**
     * @return bool If the automatic addition of definition when class exists and can be resolved is disabled.
     */
    public function useStrictMode(): bool;
}
