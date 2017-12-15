<?php

namespace yii\di\tests\code;

/**
 * EngineMarkOne
 */
class EngineMarkOne implements EngineInterface
{
    private $number;


    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mark One';
    }

    /**
     * @param int $value
     */
    public function setNumber(int $value): void
    {
        $this->number = $value;
    }

    /**
     * @return int
     */
    public function getNumer(): int
    {
        return $this->number;
    }
}