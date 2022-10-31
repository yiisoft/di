<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * EngineMarkTwo
 */
class EngineMarkTwo implements EngineInterface
{
    public const NAME = 'Mark Two';
    public const NUMBER = 2;

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
