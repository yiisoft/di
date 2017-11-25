<?php
namespace yii\di\tests\code;

/**
 * EngineMarkTwo
 */
class EngineMarkTwo implements EngineInterface
{
    private $number;

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mark Two';
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