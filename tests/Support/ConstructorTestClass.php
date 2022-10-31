<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use function func_get_args;

/**
 * ConstructorTestClass
 */
class ConstructorTestClass
{
    private array $allParameters;

    /**
     * ConstructorTestClass constructor.
     *
     * @param $parameter
     */
    public function __construct(private $parameter)
    {
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
