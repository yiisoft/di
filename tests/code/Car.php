<?php

namespace yii\di\tests\code;

/**
 * A car
 */
class Car
{
    public $class;

    private $engine;

    /**
     * Car constructor.
     * @param EngineInterface $engine
     */
    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return string
     */
    public function getEngineName(): string
    {
        return $this->engine->getName();
    }
}