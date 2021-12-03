<?php

declare(strict_types=1);

namespace Yiisoft\Di\Helpers;

/**
 * @internal
 */
final class TagHelper
{
    public static function extarctTagFromAlias(string $alias): string
    {
        return substr($alias, 4);
    }

    public static function isTagAlias(string $id): bool
    {
        return strncmp($id, 'tag@', 4) === 0;
    }
}
