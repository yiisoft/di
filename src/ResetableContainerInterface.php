<?php

namespace Yiisoft\Di;

/**
 * Container that could be reset so its services are created again when `get()` is called
 */
interface ResetableContainerInterface
{
    /**
     * Reset initialized services
     */
    public function reset(): void;
}
