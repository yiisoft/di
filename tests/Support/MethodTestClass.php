<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * MethodTestClass
 */
class MethodTestClass
{
    private $value;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
