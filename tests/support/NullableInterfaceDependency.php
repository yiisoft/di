<?php


namespace yii\di\tests\support;

class NullableInterfaceDependency
{
    public function __construct(?EngineInterface $engine)
    {
    }
}
