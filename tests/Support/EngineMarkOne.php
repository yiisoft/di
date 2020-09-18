<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * EngineMarkOne
 */
class EngineMarkOne implements EngineInterface
{
    public const NAME = 'Mark One';
    public const NUMBER = 1;

    private int $number;

    public function __construct(int $number = self::NUMBER)
    {
        $this->number = $number;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function setNumber(int $value): void
    {
        $this->number = $value;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
