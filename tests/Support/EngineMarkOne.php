<?php
namespace Yiisoft\Di\Tests\Support;

/**
 * EngineMarkOne
 */
class EngineMarkOne implements EngineInterface
{
    public const NAME = 'Mark One';

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
    public function getNumber(): int
    {
        return $this->number;
    }
}
