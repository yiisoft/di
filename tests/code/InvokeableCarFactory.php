<?php
namespace yii\di\tests\code;


use yii\di\Container;

class InvokeableCarFactory
{
    public function __invoke(Container $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}
