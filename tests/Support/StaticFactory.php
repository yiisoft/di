<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

use stdClass;

final class StaticFactory
{
    public static function create(): stdClass
    {
        return new stdClass();
    }
}
