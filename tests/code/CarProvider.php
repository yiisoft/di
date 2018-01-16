<?php


namespace yii\di\tests\code;

use yii\di\support\ServiceProvider;


class CarProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->set(Car::class, Car::class);
        $this->container->set(CarFactory::class, CarFactory::class);
    }
}