<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * ConstructorTestClass
 */
class ConstructorTestClass
{
    private $parameter;

    private array $allParameters;

    /**
     * ConstructorTestClass constructor.
     *
     * @param $parameter
     */
    public function __construct($parameter)
    {
        $this->parameter = $parameter;
        $this->allParameters = func_get_args();
    }

    /**
     * @return mixed
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    public function getAllParameters(): array
    {
        return $this->allParameters;
    }
}
