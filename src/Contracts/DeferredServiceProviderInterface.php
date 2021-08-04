<?php

declare(strict_types=1);

namespace Yiisoft\Di\Contracts;

/**
 * Represents service provider that should be deferred to register till services are
 * actually required.
 */
interface DeferredServiceProviderInterface extends ServiceProviderInterface
{
    /**
      * @return string[] a list of IDs of services provided
      */
    public function provides(): array;
}
