<?php

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;

/**
 * Produces cars
 */
class CarFactory
{
    /**
     * @param ContainerInterface $container
     * @return Car
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function create(ContainerInterface $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}
