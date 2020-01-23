<?php

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;

class InvokeableCarFactory
{
    /**
     * @param ContainerInterface $container
     * @return Car
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Car
    {
        /** @var EngineInterface $engine */
        $engine = $container->get('engine');
        return new Car($engine);
    }
}
