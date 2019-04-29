<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\support;

use yii\di\contracts\ServiceProvider;

/**
 * Base class for service providers - components responsible for class
 * registration in the Container.
 *
 * The goal of service providers is to centralize and organize in one place
 * registration of classes bound by any logic or classes with complex dependencies.
 *
 * You can simply organize registration of service and it's dependencies in a single
 * provider class except of creating bootstrap file or configuration array for the Container.
 *
 * Example:
 *
 * ```php
 * use yii\di\support\ServiceProvider;
 *
 * class CarProvider extends ServiceProvider
 * {
 *    public function register(): void
 *    {
 *        $this->registerDependencies();
 *        $this->registerService();
 *    }
 *
 *    protected function registerDependencies(): void
 *    {
 *        $container = $this->container;
 *        $container->set(EngineInterface::class, SolarEngine::class);
 *        $container->set(WheelInterface::class, [
 *            '__class' => Wheel::class,
 *            'color' => 'black',
 *        ]);
 *    }
 *
 *    protected function registerService(): void
 *    {
 *        $this->container->set(Car::class, [
 *              '__class' => Car::class,
 *              'color' => 'red',
 *        ]);
 *    }
 * }
 * ```
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 * @since 1.0
 */
abstract class ServiceProvider implements ServiceProvider
{
}
