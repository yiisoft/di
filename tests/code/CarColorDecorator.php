<?php


namespace yii\di\tests\code;


use yii\di\contracts\Decorator;

class CarColorDecorator implements Decorator
{
    /**
     * @param Car $car
     */
    public function decorate($car): void
    {
        $car->color = $this->getColorBasedOnHeuristicAlgorithm();
    }

    protected function getColorBasedOnHeuristicAlgorithm()
    {
        // dummy stub, of coarse
        return 'black';
    }
}