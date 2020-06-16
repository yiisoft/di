<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * ConstructorTestClass
 */
class ConstructorTestClass
{
    private $parameter;


    /**
     * ConstructorTestClass constructor.
     * @param $parameter
     */
    public function __construct($parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * @return mixed
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
