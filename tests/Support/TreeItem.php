<?php

declare(strict_types=1);

namespace Yiisoft\Di\Tests\Support;

/**
 * TreeItem
 */
class TreeItem
{
    private TreeItem $treeItem;

    public function __construct(self $treeItem)
    {
        $this->treeItem = $treeItem;
    }
}
