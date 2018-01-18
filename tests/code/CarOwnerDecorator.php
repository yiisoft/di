<?php


namespace yii\di\tests\code;

use yii\di\contracts\Decorator;

class CarOwnerDecorator implements Decorator
{
    /**
     * @param Car $car
     */
    public function decorate($car): void
    {
        $car->owner = 'Marcus Lom';
    }
}