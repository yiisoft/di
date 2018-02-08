<?php

namespace yii\di\tests\code;

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
