<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * TreeItem
 */
class TreeItem
{
    public function __construct(private readonly self $treeItem)
    {
    }
}
