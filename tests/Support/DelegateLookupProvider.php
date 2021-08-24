<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\CompositeContainer;
use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;

class DelegateLookupProvider implements ServiceProviderInterface
{
    public function getDefinitions(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [
            'core.di.delegates' => static function (ContainerInterface $container, CompositeContainer $delegates) {
                $delegates->attach(
                    new Container([
                        EngineInterface::class => EngineMarkOne::class,
                        SportCar::class => ['__construct()' => ['maxSpeed' => 300]],
                    ])
                );

                return $delegates;
            }

        ];
    }
}

