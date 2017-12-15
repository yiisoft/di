<?php

namespace yii\di\tests\code;

use Psr\Container\ContainerInterface;

class InvokeableCarFactory
{
    public function __invoke(ContainerInterface $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}
