<?php


namespace yii\di\tests\code;


class OptionalInterfaceDependency
{
    public function __construct(EngineInterface $engine = null)
    {

    }
}