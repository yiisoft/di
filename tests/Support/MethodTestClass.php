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

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
