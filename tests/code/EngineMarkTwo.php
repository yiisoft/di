<?php
namespace yii\di\tests\code;

/**
 * EngineMarkTwo
 */
class EngineMarkTwo implements EngineInterface
{
    const NAME = 'Mark Two';

    private $number;

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
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