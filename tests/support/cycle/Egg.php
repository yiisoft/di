<?php
namespace yii\di\tests\support\cycle;

class Egg
{
    public function __construct(Chicken $chicken)
    {
    }
}
