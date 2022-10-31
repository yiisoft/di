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

    public function __construct(private int $number = self::NUMBER)
    {
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
