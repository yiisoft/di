<?php


namespace yii\di\tests\code;

use yii\di\contracts\DecoratorInterface;

class CarOwnerDecoratorInterface implements DecoratorInterface
{
    /**
     * @param Car $car
     */
    public function decorate($car): void
    {
        $car->owner = 'Marcus Lom';
    }
}