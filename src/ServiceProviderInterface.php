<?php

declare(strict_types=1);

namespace Yiisoft\Di;

/**
 * Represents a component responsible for class registration in the Container.
 *
 * The goal of service providers is to centralize and organize in one place
 * registration of classes bound by any logic or classes with complex dependencies.
 *
 * You can organize registration of a service and its dependencies in a single
 * provider class except for creating a bootstrap file or configuration array for the Container.
 *
 * Example:
 *
 * ```php
 * class CarProvider implements ServiceProviderInterface
 * {
 *    public function getDefinitions(): array
 *    {
 *        return [
 *            'car' => ['class' => Car::class],
 *            'car-factory' => CarFactory::class,
 *            EngineInterface::class => EngineMarkOne::class,
 *        ];
 *    }
 * }
 * ```
 */
interface ServiceProviderInterface
{
    /**
     * Returns definitions for the container.
     *
     * This method:
     *
     * - Should only return definitions for the Container preventing any side effects.
     * - Should be idempotent.
     *
     * @return array Definitions for the container. Each array key is the name of the service (usually it is
     * an interface name), and a corresponding value is a service definition.
     */
    public function getDefinitions(): array;

    /**
     * Returns an array of service extensions.
     *
     * An extension is callable that returns a modified service object:
     *
     * ```php
     * static function (ContainerInterface $container, $service) {
     *     return $service->withAnotherOption(42);
     * }
     * ```
     *
     * @return array Extensions for the container services. Each array key is the name of the service to be modified
     * and a corresponding value is callable doing the job.
     */
    public function getExtensions(): array;
}
