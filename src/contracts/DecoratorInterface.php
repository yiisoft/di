<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di\contracts;

/**
 * Represents decorator of objects fetched from the container.
 *
 * Simple decorator that change car color may look like:
 * ```php
 * use yii\di\contracts\DecoratorInterface;
 *
 * class CarColorDecorator implements DecoratorInterface
 * {
 *     public function decorate($car): void
 *     {
 *         $car->color = $this->getColorBasedOnHeuristicAlgorithm();
 *     }
 *
 *     protected function getColorBasedOnHeuristicAlgorithm()
 *     {
 *         // dummy stub, of coarse
 *         return 'black';
 *     }
 * }
 * ```
 * You can add decorator to the container using `addDecorator` method, like:
 * ```php
 * $container->addDecorator(Car::class, CarColorDecorator::class);
 * ```
 * Note: decorator should be stateless as the same decorator instance will be used
 * all the time. Keep in object state only data and objects that can be used to decorate any
 * object passed  to the decorator.
 *
 * @see https://sourcemaking.com/design_patterns/decorator
 *
 * @author Dmitry Kolodko <prowwid@gmail.com>
 * @since 1.0
 */
interface DecoratorInterface
{
    /**
     * Decorates given object.
     *
     * Decorator can do anything with given object except of replacing it.
     * Note: this method should be stateless as the same decorator instance will be used
     * all the time.
     *
     * @param mixed $object object to decorate.
     */
    public function decorate($object): void;
}
