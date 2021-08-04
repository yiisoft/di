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
 *    public function getDefinitions(): array
 *    {
 *        return [
 *            'car' =>  ['__class' => Car::class],
 *            'car-factory' => CarFactory::class,
 *            EngineInterface::class => EngineMarkOne::class,
 *            ];
 *    }
 *
 * }
 * ```
 */
interface ServiceProviderInterface
{
    /**
     * Registers classes in the container.
     *
     * - This method should only set classes definitions to the Container preventing any side-effects.
     * - This method should be idempotent
     * This method may be called multiple times with different container objects,
     * or multiple times with the same object.
     *
     */
    public function getDefinitions(): array;

    public function getExtensions(): array;
}
