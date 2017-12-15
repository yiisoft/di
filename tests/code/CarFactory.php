<?php

namespace yii\di\tests\code;

use Psr\Container\ContainerInterface;

/**
 * Produces cars
 */
class CarFactory
{
    /**
     * @param ContainerInterface $container
     * @return Car
     */
    public static function create(ContainerInterface $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}