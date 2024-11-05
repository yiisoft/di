<?php

declare(strict_types=1);

namespace Yiisoft\Di\Reference;

use InvalidArgumentException;
use Yiisoft\Definitions\Reference;

use function sprintf;

/**
 * Helper class used to specify a reference to a tag.
 * For example, `TagReference::to('my-tag')` specifies a reference to all services that are tagged with `tag@my-tag`.
 */
final class TagReference
{
    private const PREFIX = 'tag@';

    private function __construct()
    {
    }

    public static function to(string $tag): Reference
    {
        return Reference::to(self::id($tag));
    }

    public static function id(string $tag): string
    {
        return self::PREFIX . $tag;
    }

    public static function extractTagFromAlias(string $alias): string
    {
        if (!str_starts_with($alias, self::PREFIX)) {
            throw new InvalidArgumentException(sprintf('Alias "%s" is not a tag alias.', $alias));
        }
        return substr($alias, 4);
    }

    public static function isTagAlias(string $id): bool
    {
        return str_starts_with($id, self::PREFIX);
    }
}
