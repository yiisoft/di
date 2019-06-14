<?php
namespace yii\di\tests\support\cycle;

class Chicken
{
    public function __construct(Egg $egg)
    {
    }
}
