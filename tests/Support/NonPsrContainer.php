<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use Psr\Container\ContainerInterface;
use stdClass;

final class NonPsrContainer implements ContainerInterface
{
    public function get(string $id)
    {
        return new stdClass();
    }

    public function has(string $id)
    {
        return false;
    }
}
