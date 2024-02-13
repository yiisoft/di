<?php

declare(strict_types=1);

namespace Yiisoft\Di\Hook;

use Closure;
use Yiisoft\Di\Container;

final class AfterBuiltHook
{
    public static function unsetInstance(): Closure
    {
        return function (Container $container, string $id) {
            /**
             * @var $this Container
             */
            unset($this->instances[$id]);
        };
    }
}
