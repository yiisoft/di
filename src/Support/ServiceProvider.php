<?php

declare(strict_types=1);

namespace Yiisoft\Di\Support;

use Yiisoft\Di\AbstractContainerConfigurator;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

abstract class ServiceProvider extends AbstractContainerConfigurator implements ServiceProviderInterface
{
    abstract public function register(AbstractContainerConfigurator $containerConfigurator): void;
}
