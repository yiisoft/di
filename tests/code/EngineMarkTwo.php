<?php

namespace yii\di\tests\code;

/**
 * EngineMarkTwo
 */
class EngineMarkTwo implements EngineInterface
{
    public const NAME = 'Mark Two';

    private $number;


    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
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
