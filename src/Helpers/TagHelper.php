<?php

declare(strict_types=1);

namespace Yiisoft\Di\Helpers;

/**
 * @internal
 */
final class TagHelper
{
    public static function extractTagFromAlias(string $alias): string
    {
        return substr($alias, 4);
    }

    public static function isTagAlias(string $id): bool
    {
        return str_starts_with($id, 'tag@');
    }
}
