<?php


namespace yii\di\tests\support;

class OptionalInterfaceDependency
{
    public function __construct(EngineInterface $engine = null)
    {
    }
}
