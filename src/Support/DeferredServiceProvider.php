<?php

declare(strict_types=1);

namespace Yiisoft\Di\Support;

use Yiisoft\Di\Contracts\DeferredServiceProviderInterface;
use Yiisoft\Di\AbstractContainerConfigurator;

/**
 * Base class for service providers that should be deferred to register till services are
 * actually required.
 *
 * Complex services with heavy dependencies might create redundant load during bootstrapping
 * of an application so to reduce actions performed during the container bootstrap you can
 *
 * Deferred providers can be added to the Container like basic providers but won't register
 * any definitions to the container till one of the classes listed in `provides` method would
 * be requested from container. Example:
 *
 * ```php
 * use Yiisoft\Di\Support\DeferredServiceProvider;
 *
 * class CarProvider extends DeferredServiceProvider
 * {
 *     public function provides(): array
 *     {
 *         return [
 *             Car::class,
 *             CarFactory::class,
 *         ];
 *     }
 *
 *     public function getDefinitions(): array
 *     {
 *         return [
 *              'car' =>  ['__class' => Car::class],
 *              'car-factory' => CarFactory::class,
 *              EngineInterface::class => EngineMarkOne::class,
 *             ];
 *     }
 * }
 *
 * $container->addProvider(CarProvider::class);
 *
 * $container->has(EngineInterface::class); // returns false provider wasn't registered
 *
 * $engine = $container->get(EngineInterface::class); // returns EngineMarkOne as provider was
 * // registered once EngineInterface was requested from the container.
 * ```
 */
abstract class DeferredServiceProvider implements DeferredServiceProviderInterface
{
    /**
     * Lists classes provided by service provider. Should be a list of class names
     * or identifiers. Example:
     *
     * ```php
     * return [
     *      Car::class,
     *      EngineInterface::class,
     *      'car-factory',
     * ];
     * ```
     *
     * @return array list of provided classes.
     */
    abstract public function provides(): array;

    /**
     * Identifies whether service provider would register definition for
     * given identifier or not.
     *
     * @param string $id class, interface or identifier in the Container.
     * @return bool whether service provider would register definition or not.
     */
    public function hasDefinitionFor(string $id): bool
    {
        return \in_array($id, $this->provides(), true);
    }
}
