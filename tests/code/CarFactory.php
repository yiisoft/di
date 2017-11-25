<?php

namespace yii\di\tests\code;
use yii\di\Container;

/**
 * Produces cars
 */
class CarFactory
{
    /**
     * @param Container $container
     * @return Car
     */
    public static function create(Container $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}