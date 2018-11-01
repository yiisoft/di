<?php


namespace yii\di\tests\code;


class NullableInterfaceDependency
{
    public function __construct(?EngineInterface $engine)
    {

    }
}