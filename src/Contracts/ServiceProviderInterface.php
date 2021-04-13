<?php

declare(strict_types=1);

namespace Yiisoft\Di\Contracts;

use Yiisoft\Di\Container;

/**
 * Represents a component responsible for class registration in the Container.
 *
 * The goal of service providers is to centralize and organize in one place
 * registration of classes bound by any logic or classes with complex dependencies.
 *
 * You can simply organize registration of service and it's dependencies in a single
 * provider class except of creating bootstrap file or configuration array for the Container.
 *
 * Example:
 * ```php
 * class CarProvider implements ServiceProviderInterface
 * {
 *    public function register(Container $container): void
 *    {
 *        $this->registerDependencies($container);
 *        $this->registerService($container);
 *    }
 *
 *    protected function registerDependencies($container): void
 *    {
 *        $container->set(EngineInterface::class, SolarEngine::class);
 *        $container->set(WheelInterface::class, [
 *            'class' => Wheel::class,
 *            '$color' => 'black',
 *        ]);
 *    }
 *
 *    protected function registerService($container): void
 *    {
 *        $container->set(Car::class, [
 *              'class' => Car::class,
 *              '$color' => 'red',
 *        ]);
 *    }
 * }
 * ```
 */
interface ServiceProviderInterface
{
    /**
     * Registers classes in the container.
     *
     * - This method should only set class definitions to the Container and
     *   not have any side-effects.
     * - This method should be idempotent
     * This method may be called multiple times with different container objects,
     * or multiple times with the same object.
     *
     * @param Container $container the container in which to register the services.
     */
    public function register(Container $container): void;
}
